<?php

namespace Tests\Feature;

use App\Jobs\SendNotificationEmail;
use App\Models\Course;
use App\Models\NotificationPreference;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_own_notifications_and_can_read_them(): void
    {
        $student = $this->student();
        app(NotificationService::class)->forStudent($student, 'status', 'Atualização', 'Seu pedido mudou.', null, 'test:one');
        app(NotificationService::class)->forStudent($student, 'status', 'Duplicada', 'Não deve duplicar.', null, 'test:one');
        $list = $this->actingAs($student, 'api')->getJson('/api/notifications')->assertOk()->assertJsonPath('total', 1);
        $id = $list->json('data.0.id');
        $this->actingAs($student, 'api')->getJson('/api/notifications/count')->assertJsonPath('unread', 1);
        $this->actingAs($student, 'api')->postJson("/api/notifications/{$id}/read")->assertOk();
        $this->actingAs($student, 'api')->getJson('/api/notifications/count')->assertJsonPath('unread', 0);
    }

    public function test_staff_message_creates_internal_notice_without_exposing_to_other_student(): void
    {
        $student = $this->student();
        $other = $this->student();
        $request = RequestModel::create(['user_id' => $student->id, 'type_id' => TypeRequest::create(['name' => 'Tipo'])->id, 'subject' => 'Assunto', 'description' => 'Descrição', 'protocol' => 'n-'.uniqid()]);
        $staff = StaffAdmin::create(['name' => 'CRADT', 'email' => uniqid().'@example.test', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'role' => 'cradt', 'password' => 'secret', 'must_change_password' => false]);
        $this->assertNotNull($request->fresh()->user);
        $resp = $this->actingAs($staff, 'staff_admins')->postJson("/api/requests/{$request->id}/messages", ['content' => 'Retorno da equipe'])->assertCreated();
        $this->actingAs($student, 'api')->getJson('/api/notifications')->assertJsonPath('total', 1)->assertJsonPath('data.0.category', 'message');
        $this->actingAs($other, 'api')->getJson('/api/notifications')->assertJsonPath('total', 0);
    }

    public function test_email_preference_enqueues_delivery_without_sending_real_email(): void
    {
        Queue::fake();
        $student = $this->student();
        $student->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($student, 'api')->putJson('/api/notification-preferences', ['category' => 'status', 'email_enabled' => true, 'internal_enabled' => true])->assertOk();
        app(NotificationService::class)->forStudent($student, 'status', 'Atualização', 'Conteúdo de teste', null, 'email:test');
        Queue::assertPushed(SendNotificationEmail::class);
    }

    public function test_unverified_email_cannot_enable_email_notifications_or_receive_email_job(): void
    {
        Queue::fake();
        $student = $this->student();

        $this->actingAs($student, 'api')->putJson('/api/notification-preferences', [
            'category' => 'status', 'email_enabled' => true, 'internal_enabled' => true,
        ])->assertUnprocessable()->assertJsonPath('email_verification_required', true);

        // Legacy or previously stored preference must not bypass verification at dispatch time.
        NotificationPreference::create([
            'recipient_type' => 'student', 'recipient_id' => $student->id, 'category' => 'status',
            'internal_enabled' => true, 'email_enabled' => true,
        ]);
        app(NotificationService::class)->forStudent($student, 'status', 'Atualização', 'Texto neutro', null, 'unverified:test');

        Queue::assertNothingPushed();
        $this->assertDatabaseHas('notification_records', ['idempotency_key' => 'unverified:test', 'email_status' => 'skipped']);
    }

    public function test_staff_notifications_and_email_are_suppressed_after_request_scope_is_revoked(): void
    {
        Queue::fake();
        Mail::fake();
        $student = $this->student();
        $type = TypeRequest::create(['name' => 'Tipo de escopo']);
        $request = RequestModel::create([
            'user_id' => $student->id,
            'course_id' => $student->course_id,
            'type_id' => $type->id,
            'subject' => 'Assunto confidencial',
            'description' => 'Descrição',
            'protocol' => 'scope-'.uniqid(),
            'responsible_sector' => 'CRADT',
        ]);
        $staff = StaffAdmin::create([
            'name' => 'Atendente QA', 'email' => uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'cradt', 'password' => 'secret', 'must_change_password' => false,
            'is_active' => true,
        ]);
        $staff->forceFill(['email_verified_at' => now()])->save();
        $scope = StaffAccessScope::create([
            'staff_admin_id' => $staff->id,
            'course_id' => $student->course_id,
            'sector' => 'CRADT',
            'abilities' => ['view_request'],
            'starts_at' => now()->subMinute(),
            'reason' => 'Acesso de atendimento temporário',
        ]);
        NotificationPreference::create([
            'recipient_type' => 'staff_admin', 'recipient_id' => $staff->id, 'category' => 'message',
            'internal_enabled' => true, 'email_enabled' => true,
        ]);
        $notification = app(NotificationService::class)->forStaff(
            $staff,
            'message',
            'Resposta do aluno',
            'Conteúdo ligado ao pedido.',
            $request,
            'scope-revoke:notification',
        );
        $this->assertNotNull($notification);
        $this->actingAs($staff, 'staff_admins')->getJson('/api/notifications')->assertOk()->assertJsonPath('total', 1);

        $scope->update(['revoked_at' => now()]);

        $this->actingAs($staff, 'staff_admins')->getJson('/api/notifications')->assertOk()->assertJsonPath('total', 0);
        $this->actingAs($staff, 'staff_admins')->getJson('/api/notifications/count')->assertOk()->assertJsonPath('unread', 0);
        $this->actingAs($staff, 'staff_admins')->postJson('/api/notifications/'.$notification->id.'/read')->assertNotFound();
        (new SendNotificationEmail($notification->id))->handle();

        Mail::assertNothingOutgoing();
        $this->assertDatabaseHas('notification_records', ['id' => $notification->id, 'email_status' => 'skipped']);
    }

    public function test_deactivated_staff_cannot_access_notifications_with_an_existing_session(): void
    {
        $staff = StaffAdmin::create([
            'name' => 'Equipe QA', 'email' => uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'cradt', 'password' => 'secret', 'must_change_password' => false,
            'is_active' => true,
        ]);
        app(NotificationService::class)->forStaff($staff, 'status', 'Atualização', 'Mensagem interna.', null, 'inactive:staff');
        $this->actingAs($staff, 'staff_admins');
        $staff->update(['is_active' => false]);

        $this->getJson('/api/notifications')->assertForbidden();
        $this->getJson('/api/notifications/count')->assertForbidden();
        $this->getJson('/api/notification-preferences')->assertForbidden();
    }

    private function student(): User
    {
        $course = Course::create(['name' => 'Curso '.uniqid(), 'code' => 'C'.uniqid()]);

        return User::create(['name' => 'Aluno', 'email' => uniqid().'@student.test', 'password' => 'secret', 'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT), 'phone' => '81999999999', 'matricula' => 'M'.uniqid(), 'course_id' => $course->id, 'role' => 'student', 'birthday' => '2000-01-01']);
    }
}
