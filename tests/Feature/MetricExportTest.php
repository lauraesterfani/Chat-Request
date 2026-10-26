<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use App\Services\MetricExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class MetricExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_xlsx_is_real_ooxml_text_cells_and_contains_only_synthetic_aggregates(): void
    {
        $campus = Campus::create(['name' => 'Campus sintético', 'code' => 'CS']);
        $course = Course::create(['name' => '=HYPERLINK("https://invalid.test","Fórmula")', 'code' => 'EXP', 'campus_id' => $campus->id]);
        $student = User::create(['name' => 'Nome confidencial aluno', 'email' => 'export-student@example.test', 'cpf' => '90000008801', 'phone' => '81900008801', 'matricula' => 'MATRICULA-PRIVADA', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Serviço de exportação']);
        RequestModel::create(['user_id' => $student->id, 'course_id' => $course->id, 'campus_id' => $campus->id, 'type_id' => $type->id, 'subject' => '=1+1', 'description' => 'Descrição confidencial', 'status' => 'pending', 'responsible_sector' => 'CRADT', 'protocol' => '20261026-001234']);
        $admin = $this->admin();

        $date = now()->utc()->toDateString();
        $filters = ['from' => $date, 'to' => $date, 'course_id' => $course->id, 'campus_id' => $campus->id, 'type_id' => $type->id, 'sector' => 'CRADT', 'status' => 'pending'];
        $apiMetrics = $this->actingAs($admin, 'staff_admins')->getJson('/api/metrics?'.http_build_query($filters))
            ->assertOk()
            ->assertJsonPath('distribution.by_service.0.label', 'Serviço de exportação');
        $exportMetrics = app(MetricExportService::class)->build($admin, $filters);
        $this->assertSame($apiMetrics->json('distribution'), $exportMetrics['summary']['distribution']);
        $this->assertSame($apiMetrics->json('cohorts'), $exportMetrics['summary']['cohorts']);
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/metrics/exports/xlsx', [
            ...$filters,
        ])
            ->assertCreated()
            ->assertJsonPath('format', 'xlsx');
        Storage::disk('local')->assertExists('metrics-exports/'.$created->json('id').'.xlsx');
        $download = $this->get($created->json('download_path'));
        $download->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $bytes = $download->getContent();
        $this->assertStringStartsWith('PK', $bytes);

        $temporary = tempnam(sys_get_temp_dir(), 'metric-export-test-');
        file_put_contents($temporary, $bytes);
        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($temporary) === true);
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
        } finally {
            @unlink($temporary);
        }

        $this->assertIsString($sheet);
        $this->assertStringContainsString('inlineStr', $sheet);
        $this->assertStringNotContainsString('<f', $sheet);
        $this->assertStringContainsString('=HYPERLINK', $sheet);
        $this->assertStringContainsString('Fórmula', $sheet);
        $this->assertStringContainsString('Campus sintético', $sheet);
        $this->assertStringContainsString('Serviço de exportação', $sheet);
        $this->assertStringContainsString('CRADT', $sheet);
        $this->assertStringContainsString('Requerimentos no recorte', $sheet);
        $this->assertStringContainsString('Distribuições de requerimentos por coorte de abertura', $sheet);
        $this->assertStringContainsString('Coortes separadas', $sheet);
        $this->assertStringContainsString('Serviço de exportação', $sheet);
        $this->assertStringContainsString('>1</t>', $sheet);
        $this->assertStringNotContainsString('Nome confidencial aluno', $sheet);
        $this->assertStringNotContainsString('MATRICULA-PRIVADA', $sheet);
        $this->assertStringNotContainsString('Descrição confidencial', $sheet);
        $this->assertStringNotContainsString('=1+1', $sheet);

        $this->assertDatabaseHas('audit_records', ['action' => 'metrics.export.requested', 'resource_id' => $created->json('id')]);
        $this->assertDatabaseHas('audit_records', ['action' => 'metrics.export.downloaded', 'resource_id' => $created->json('id')]);
        Storage::disk('local')->assertMissing('metrics-exports/'.$created->json('id').'.xlsx');
        Storage::disk('local')->assertMissing('metrics-exports/'.$created->json('id').'.json');
    }

    public function test_pdf_export_has_pdf_signature_and_report_metadata(): void
    {
        $admin = $this->admin();
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/metrics/exports/pdf', ['from' => '2026-10-01', 'to' => '2026-10-26'])
            ->assertCreated()
            ->assertJsonPath('format', 'pdf');

        $download = $this->get($created->json('download_path'));
        $download->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $pdf = $download->getContent();
        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertMatchesRegularExpression('/startxref\n\d+\n%%EOF/', $pdf);
        preg_match('/startxref\n(\d+)\n%%EOF/', $pdf, $xref);
        $this->assertSame('xref', substr($pdf, (int) $xref[1], 4));
    }

    public function test_download_is_denied_after_the_effective_scope_is_revoked(): void
    {
        $course = Course::create(['name' => 'Curso com acesso temporário', 'code' => 'TMP']);
        $admin = $this->admin();
        $scope = StaffAccessScope::create(['staff_admin_id' => $admin->id, 'course_id' => $course->id, 'abilities' => ['view_request'], 'reason' => 'Teste isolado']);
        $created = $this->actingAs($admin, 'staff_admins')->postJson('/api/metrics/exports/xlsx')->assertCreated();

        $scope->update(['revoked_at' => now()]);
        $this->get($created->json('download_path'))->assertForbidden();
        $this->assertDatabaseMissing('audit_records', ['action' => 'metrics.export.downloaded', 'resource_id' => $created->json('id')]);
    }

    public function test_export_requires_effective_view_permission(): void
    {
        $admin = $this->admin();
        $course = Course::create(['name' => 'Curso sem consulta', 'code' => 'SCQ']);
        StaffAccessScope::create(['staff_admin_id' => $admin->id, 'course_id' => $course->id, 'abilities' => ['reply_request'], 'reason' => 'Sem capacidade de consulta']);

        $this->actingAs($admin, 'staff_admins')->postJson('/api/metrics/exports/pdf')->assertForbidden();
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_real_export_route_requires_authentication(): void
    {
        $this->postJson('/api/metrics/exports/pdf')->assertUnauthorized();
    }

    public function test_real_export_route_denies_student_and_applies_the_password_change_gate(): void
    {
        $course = Course::create(['name' => 'Curso de rota', 'code' => 'ROT']);
        $student = User::create(['name' => 'Estudante sem permissão', 'email' => 'route-student@example.test', 'cpf' => '90000008804', 'phone' => '81900008804', 'matricula' => 'ROT001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $this->actingAs($student, 'api')->postJson('/api/metrics/exports/pdf')->assertForbidden();

        $student->update(['must_change_password' => true]);
        $this->actingAs($student, 'api')->postJson('/api/metrics/exports/pdf')
            ->assertForbidden()
            ->assertJsonPath('must_change_password', true);
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_real_export_route_denies_inactive_administration(): void
    {
        $inactiveAdmin = $this->admin('inactive', '90000008805');
        $inactiveAdmin->update(['is_active' => false]);
        $this->actingAs($inactiveAdmin, 'staff_admins')->postJson('/api/metrics/exports/pdf')->assertForbidden();
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_real_export_route_rejects_unsupported_format(): void
    {
        $activeAdmin = $this->admin();
        $this->actingAs($activeAdmin, 'staff_admins')->postJson('/api/metrics/exports/csv')->assertNotFound();
        $this->assertDatabaseCount('audit_records', 0);
    }

    public function test_synchronous_export_rejects_more_than_the_conservative_row_limit(): void
    {
        $course = Course::create(['name' => 'Curso grande volume', 'code' => 'GVE']);
        $student = User::create(['name' => 'Aluno carga sintética', 'email' => 'large-export@example.test', 'cpf' => '90000008803', 'phone' => '81900008803', 'matricula' => 'GVE001', 'birthday' => '2000-01-01', 'password' => 'secret', 'role' => 'student', 'course_id' => $course->id]);
        $type = TypeRequest::create(['name' => 'Serviço de volume']);
        $now = now();
        $rows = [];
        for ($index = 0; $index <= MetricExportService::MAX_SYNCHRONOUS_ROWS; $index++) {
            $rows[] = [
                'id' => (string) Str::uuid(),
                'user_id' => $student->id,
                'type_id' => $type->id,
                'subject' => 'Carga sintética',
                'description' => 'Carga sintética',
                'status' => 'pending',
                'protocol' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('requests')->insert($chunk);
        }

        $admin = $this->admin();
        $this->actingAs($admin, 'staff_admins')->postJson('/api/metrics/exports/xlsx')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('filters')
            ->assertJsonPath('errors.filters.0', 'O relatório ultrapassa o limite de 5000 requerimentos para geração síncrona. Reduza o período ou os filtros.');
    }

    private function admin(string $suffix = '', string $cpf = '90000008802'): StaffAdmin
    {
        return StaffAdmin::create([
            'name' => 'Admin Exportação',
            'email' => 'metrics-export-admin'.($suffix === '' ? '' : '-'.$suffix).'@example.test',
            'cpf' => $cpf,
            'role' => 'admin',
            'password' => 'secret',
            'must_change_password' => false,
        ]);
    }
}
