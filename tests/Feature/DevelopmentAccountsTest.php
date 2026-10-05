<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\StaffAdmin;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_repeatable_accounts_and_preserve_course_ids(): void
    {
        $this->seed(DatabaseSeeder::class);
        $courseId = Course::where('code', 'TSI-2025')->value('id');
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($courseId, Course::where('code', 'TSI-2025')->value('id'));
        $this->assertSame(2, User::where('email', 'like', 'qa.student.%@example.test')->count());
        $this->assertSame(4, StaffAdmin::where('email', 'like', 'qa.%@example.test')->count());
        $this->postJson('/api/login', ['matricula' => '20241TSIIG001', 'password' => 'ChatRequest-Teste2026!'])
            ->assertOk()->assertJsonPath('must_change_password', false);
        $this->postJson('/api/login/staff', ['email' => 'qa.coordenacao@example.test', 'password' => 'ChatRequest-Teste2026!'])
            ->assertOk()->assertJsonPath('user.role', 'coordenacao');
    }
}
