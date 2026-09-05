<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $student = $this->student(); $other = $this->student();
        $request = RequestModel::create(['user_id'=>$student->id,'type_id'=>TypeRequest::create(['name'=>'Tipo'])->id,'subject'=>'Assunto','description'=>'Descrição','protocol'=>'n-'.uniqid()]);
        $staff = StaffAdmin::create(['name'=>'CRADT','email'=>uniqid().'@example.test','cpf'=>str_pad((string)random_int(1,99999999999),11,'0',STR_PAD_LEFT),'role'=>'cradt','password'=>'secret','must_change_password'=>false]);
        $this->assertNotNull($request->fresh()->user);
        $resp = $this->actingAs($staff,'staff_admins')->postJson("/api/requests/{$request->id}/messages",['content'=>'Retorno da equipe'])->assertCreated();
        $this->actingAs($student,'api')->getJson('/api/notifications')->assertJsonPath('total',1)->assertJsonPath('data.0.category','message');
        $this->actingAs($other,'api')->getJson('/api/notifications')->assertJsonPath('total',0);
    }

    public function test_email_preference_enqueues_delivery_without_sending_real_email(): void
    {
        Queue::fake();
        $student = $this->student();
        $this->actingAs($student, 'api')->putJson('/api/notification-preferences', ['category'=>'status','email_enabled'=>true,'internal_enabled'=>true])->assertOk();
        app(NotificationService::class)->forStudent($student, 'status', 'Atualização', 'Conteúdo de teste', null, 'email:test');
        Queue::assertPushed(\App\Jobs\SendNotificationEmail::class);
    }

    private function student(): User { $course=Course::create(['name'=>'Curso '.uniqid(),'code'=>'C'.uniqid()]); return User::create(['name'=>'Aluno','email'=>uniqid().'@student.test','password'=>'secret','cpf'=>str_pad((string)random_int(1,99999999999),11,'0',STR_PAD_LEFT),'phone'=>'81999999999','matricula'=>'M'.uniqid(),'course_id'=>$course->id,'role'=>'student','birthday'=>'2000-01-01']); }
}
