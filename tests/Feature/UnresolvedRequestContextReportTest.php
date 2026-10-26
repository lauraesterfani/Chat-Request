<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnresolvedRequestContextReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_lists_missing_historical_context_without_exposing_student_data_or_mutating_records(): void
    {
        $course = Course::create(['name' => 'Curso de teste', 'code' => 'CTX']);
        $student = User::create([
            'name' => 'Nome que não deve aparecer',
            'email' => 'private-context@example.test',
            'cpf' => '90000006001',
            'phone' => '81900006001',
            'matricula' => 'CTX001',
            'birthday' => '2000-01-01',
            'password' => 'secret',
            'role' => 'student',
            'course_id' => $course->id,
        ]);
        $type = TypeRequest::create(['name' => 'Requerimento teste']);
        $legacy = RequestModel::create([
            'user_id' => $student->id,
            'type_id' => $type->id,
            'subject' => 'Contexto antigo',
            'description' => 'Descrição',
            'status' => 'pending',
            'protocol' => '20261026-060001',
        ]);

        $this->artisan('requests:unresolved-access-contexts')
            ->expectsOutputToContain('Sem curso histórico confirmado: 1')
            ->expectsOutputToContain($legacy->id)
            ->doesntExpectOutputToContain('private-context@example.test')
            ->assertSuccessful();

        $this->assertNull($legacy->fresh()->course_id);
        $this->assertNull($legacy->fresh()->campus_id);
    }
}
