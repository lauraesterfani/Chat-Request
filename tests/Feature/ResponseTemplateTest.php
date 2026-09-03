<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\ResponseTemplate;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_a_response_template_and_its_relationships_are_persisted(): void
    {
        $staff = $this->staff();
        $firstType = $this->type('Abono de faltas');
        $secondType = $this->type('Cancelamento de matrícula');

        $response = $this->actingAs($staff, 'staff_admins')->postJson('/api/response-templates', [
            'title' => 'Documentação incompleta',
            'content' => 'Olá! Envie os documentos indicados para seguirmos com a análise.',
            'is_active' => true,
            'type_request_ids' => [$firstType->id, $secondType->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('title', 'Documentação incompleta')
            ->assertJsonCount(2, 'type_requests');

        $template = ResponseTemplate::firstOrFail();
        $this->assertSame($staff->id, $template->created_by);
        $this->assertTrue($template->is_active);
        $this->assertEqualsCanonicalizing(
            [$firstType->id, $secondType->id],
            $template->typeRequests()->pluck('type_requests.id')->all()
        );
    }

    public function test_admin_can_access_template_administration(): void
    {
        $admin = $this->staff();
        $admin->update(['role' => 'admin']);

        $this->actingAs($admin, 'staff_admins')
            ->getJson('/api/response-templates')
            ->assertOk();
    }

    public function test_student_cannot_create_a_response_template(): void
    {
        $student = $this->student();
        $type = $this->type('Declaração');

        $this->actingAs($student, 'api')
            ->postJson('/api/response-templates', [
                'title' => 'Não autorizado',
                'content' => 'Conteúdo que não deve ser salvo.',
                'type_request_ids' => [$type->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('response_templates', 0);
    }

    public function test_student_cannot_access_the_administrative_requests_listing(): void
    {
        $this->actingAs($this->student(), 'api')
            ->getJson('/api/admin/requests')
            ->assertForbidden();
    }

    public function test_staff_can_edit_a_response_template(): void
    {
        $staff = $this->staff();
        $firstType = $this->type('Aproveitamento');
        $secondType = $this->type('Trancamento');
        $template = $this->template($staff, $firstType);

        $this->actingAs($staff, 'staff_admins')
            ->putJson("/api/response-templates/{$template->id}", [
                'title' => 'Resposta revisada',
                'content' => 'Este é o texto atualizado para o atendimento.',
                'is_active' => false,
                'type_request_ids' => [$secondType->id],
            ])
            ->assertOk()
            ->assertJsonPath('title', 'Resposta revisada');

        $template->refresh();
        $this->assertSame('Este é o texto atualizado para o atendimento.', $template->content);
        $this->assertFalse($template->is_active);
        $this->assertSame([$secondType->id], $template->typeRequests()->pluck('type_requests.id')->all());
    }

    public function test_staff_can_deactivate_a_response_template(): void
    {
        $staff = $this->staff();
        $template = $this->template($staff, $this->type('Histórico escolar'));

        $this->actingAs($staff, 'staff_admins')
            ->patchJson("/api/response-templates/{$template->id}/status", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        $this->assertDatabaseHas('response_templates', ['id' => $template->id, 'is_active' => 0]);
    }

    public function test_inactive_templates_are_not_available_during_service(): void
    {
        $staff = $this->staff();
        $type = $this->type('Segunda chamada');
        $active = $this->template($staff, $type, true, 'Disponível');
        $this->template($staff, $type, false, 'Indisponível');

        $this->actingAs($staff, 'staff_admins')
            ->getJson("/api/response-templates/active?type_request_id={$type->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $active->id);
    }

    public function test_active_templates_are_filtered_by_request_type(): void
    {
        $staff = $this->staff();
        $firstType = $this->type('Mudança de turno');
        $secondType = $this->type('Reintegração');
        $matching = $this->template($staff, $firstType, true, 'Tipo correto');
        $this->template($staff, $secondType, true, 'Outro tipo');

        $this->actingAs($staff, 'staff_admins')
            ->getJson("/api/response-templates/active?type_request_id={$firstType->id}")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $matching->id);
    }

    public function test_creation_fails_for_unknown_request_type(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff, 'staff_admins')
            ->postJson('/api/response-templates', [
                'title' => 'Tipo inválido',
                'content' => 'Conteúdo válido.',
                'type_request_ids' => ['018f755d-1c4f-7ac7-8ab9-ecbfc3be7f32'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type_request_ids.0');
    }

    public function test_creation_fails_when_title_or_content_is_blank(): void
    {
        $staff = $this->staff();
        $type = $this->type('Diploma');

        $this->actingAs($staff, 'staff_admins')
            ->postJson('/api/response-templates', [
                'title' => '',
                'content' => '   ',
                'type_request_ids' => [$type->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    private function staff(): StaffAdmin
    {
        return StaffAdmin::create([
            'name' => 'Equipe Técnica',
            'email' => 'staff'.uniqid().'@example.test',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'role' => 'staff',
            'password' => 'secret',
            'must_change_password' => false,
        ]);
    }

    private function student(): User
    {
        $course = Course::create(['name' => 'Curso '.uniqid(), 'code' => 'C'.uniqid()]);

        return User::create([
            'name' => 'Aluno de teste',
            'email' => 'student'.uniqid().'@example.test',
            'password' => 'secret',
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'phone' => '81999999999',
            'matricula' => 'M'.uniqid(),
            'course_id' => $course->id,
            'role' => 'student',
            'birthday' => '2000-01-01',
        ]);
    }

    private function type(string $name): TypeRequest
    {
        return TypeRequest::create(['name' => $name]);
    }

    private function template(StaffAdmin $staff, TypeRequest $type, bool $active = true, string $title = 'Modelo'): ResponseTemplate
    {
        $template = ResponseTemplate::create([
            'title' => $title,
            'content' => 'Conteúdo de teste.',
            'is_active' => $active,
            'created_by' => $staff->id,
        ]);
        $template->typeRequests()->attach($type->id);

        return $template;
    }
}
