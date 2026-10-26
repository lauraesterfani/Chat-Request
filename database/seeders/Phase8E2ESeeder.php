<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Course;
use App\Models\Request as RequestModel;
use App\Models\StaffAccessScope;
use App\Models\StaffAdmin;
use App\Models\TypeRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

/** Fictitious isolated data for the API-backed Phase 8 browser test only. */
class Phase8E2ESeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new LogicException('Phase8E2ESeeder só pode ser executado em APP_ENV=testing.');
        }

        $this->call([CourseSeeder::class, TypeRequestSeeder::class, StaffAdminSeeder::class]);

        $campusA = Campus::create(['name' => 'Campus QA Norte', 'code' => 'QA-N']);
        $campusB = Campus::create(['name' => 'Campus QA Sul', 'code' => 'QA-S']);
        $courseA = Course::create(['name' => 'Curso QA Norte', 'code' => 'QA-N1', 'campus_id' => $campusA->id]);
        $courseB = Course::create(['name' => 'Curso QA Sul', 'code' => 'QA-S1', 'campus_id' => $campusB->id]);
        $type = TypeRequest::query()->firstOrFail();

        $studentA = $this->student('Campus Norte', 'norte', $courseA);
        $studentB = $this->student('Campus Sul', 'sul', $courseB);
        $requestA = RequestModel::create([
            'user_id' => $studentA->id,
            'course_id' => $courseA->id,
            'campus_id' => $campusA->id,
            'type_id' => $type->id,
            'subject' => 'Pedido autorizado QA Campus Norte',
            'description' => 'Registro fictício para validar acesso por campus.',
            'status' => 'pending',
            'protocol' => '20261026-810001',
            'responsible_sector' => 'CRADT',
        ]);
        RequestModel::create([
            'user_id' => $studentB->id,
            'course_id' => $courseB->id,
            'campus_id' => $campusB->id,
            'type_id' => $type->id,
            'subject' => 'Pedido restrito QA Campus Sul',
            'description' => 'Registro fictício de outro campus; não deve ser exposto.',
            'status' => 'pending',
            'protocol' => '20261026-810002',
            'responsible_sector' => 'CRADT',
        ]);

        $admin = StaffAdmin::query()->where('email', 'qa.admin@example.test')->firstOrFail();
        $cradt = StaffAdmin::query()->where('email', 'qa.cradt@example.test')->firstOrFail();

        // A revoked historical grant keeps the CRADT account in restricted mode;
        // the browser test then creates and revokes the only active grant.
        StaffAccessScope::create([
            'staff_admin_id' => $cradt->id,
            'campus_id' => $campusA->id,
            'sector' => 'CRADT',
            'abilities' => ['view_request'],
            'starts_at' => now()->subDay(),
            'revoked_at' => now()->subMinute(),
            'revoked_by' => $admin->id,
            'reason' => 'Registro fictício para ativar escopo restrito no teste.',
        ]);

        // Keep the first request reference explicit so seeder changes cannot
        // accidentally leave the authorized sample without its required context.
        if (! $requestA->campus_id || $requestA->course_id !== $courseA->id) {
            throw new LogicException('Fixture de requerimento do Campus Norte inválida.');
        }
    }

    private function student(string $name, string $suffix, Course $course): User
    {
        return User::create([
            'name' => 'Aluno QA '.$name,
            'email' => 'aluno.'.$suffix.'@example.test',
            'cpf' => $suffix === 'norte' ? '90000008101' : '90000008102',
            'phone' => $suffix === 'norte' ? '81900008101' : '81900008102',
            'matricula' => $suffix === 'norte' ? 'QA08101' : 'QA08102',
            'birthday' => '2000-01-01',
            'password' => Hash::make('Fase8-QA-somente-teste!'),
            'role' => 'student',
            'course_id' => $course->id,
        ]);
    }
}
