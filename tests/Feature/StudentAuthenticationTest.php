<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_logs_in_with_normalized_enrollment_number(): void
    {
        $student = $this->student('20241TSIIG0249', false);

        $this->postJson('/api/login', ['matricula' => ' 20241tsiig0249 ', 'password' => 'Senha@123'])
            ->assertOk()
            ->assertJsonPath('user.id', $student->id)
            ->assertJsonPath('must_change_password', false)
            ->assertJsonStructure(['token', 'expires_in']);
    }

    public function test_invalid_enrollment_or_password_receives_the_same_generic_response(): void
    {
        $this->student('20241TGQIG033', false);

        $unknown = $this->postJson('/api/login', ['matricula' => '20241ABCIG999', 'password' => 'Senha@123']);
        $wrongPassword = $this->postJson('/api/login', ['matricula' => '20241TGQIG033', 'password' => 'Errada@123']);

        $unknown->assertUnprocessable()->assertJsonPath('errors.matricula.0', 'Credenciais inválidas.');
        $wrongPassword->assertUnprocessable()->assertJsonPath('errors.matricula.0', 'Credenciais inválidas.');
    }

    public function test_pending_student_is_blocked_until_password_is_changed_then_can_use_a_new_token(): void
    {
        $student = $this->student('20242TSIIG0249', true);
        $login = $this->postJson('/api/login', ['matricula' => $student->matricula, 'password' => 'Senha@123'])->assertOk();
        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/requests')->assertForbidden()
            ->assertJsonPath('must_change_password', true);
        $this->withToken($token)->postJson('/api/auth/change-initial-password', [
            'current_password' => 'Senha@123',
            'new_password' => 'NovaSenha@123',
            'new_password_confirmation' => 'NovaSenha@123',
        ])->assertOk()->assertJsonPath('must_change_password', false)->assertJsonStructure(['token']);

        $student->refresh();
        $this->assertFalse($student->must_change_password);
        $this->assertTrue(Hash::check('NovaSenha@123', $student->password));

        $newToken = $this->postJson('/api/login', ['matricula' => $student->matricula, 'password' => 'NovaSenha@123'])
            ->assertOk()->json('token');
        $this->withToken($newToken)->getJson('/api/requests')->assertOk();
    }

    public function test_password_change_rejects_the_wrong_current_password_and_public_registration_is_unavailable(): void
    {
        $student = $this->student('20241ADSIG123', true);
        $token = $this->postJson('/api/login', ['matricula' => $student->matricula, 'password' => 'Senha@123'])
            ->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/auth/change-initial-password', [
            'current_password' => 'Incorreta@123',
            'new_password' => 'NovaSenha@123',
            'new_password_confirmation' => 'NovaSenha@123',
        ])->assertUnprocessable();

        $this->postJson('/api/register', ['matricula' => 'matricula-invalida'])->assertNotFound();
    }

    private function student(string $matricula, bool $mustChangePassword): User
    {
        $course = Course::create(['name' => 'Curso '.uniqid(), 'code' => 'C'.uniqid()]);

        return User::create([
            'name' => 'Aluno de autenticação',
            'email' => uniqid().'@student.test',
            'password' => Hash::make('Senha@123'),
            'cpf' => str_pad((string) random_int(1, 99999999999), 11, '0', STR_PAD_LEFT),
            'phone' => '81999999999',
            'matricula' => $matricula,
            'course_id' => $course->id,
            'role' => User::ROLE_STUDENT,
            'birthday' => '2000-01-01',
            'must_change_password' => $mustChangePassword,
        ]);
    }
}
