<?php

namespace Tests\Feature;

use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    public function test_forbidden_response_does_not_expose_identity_or_roles(): void
    {
        $student = User::factory()->make(['role' => 'student', 'name' => 'Pessoa Fictícia']);
        $request = Request::create('/private', 'GET');
        $request->setUserResolver(fn () => $student);

        $response = (new RoleMiddleware)->handle($request, fn () => response()->json(['ok' => true]), 'admin');

        $this->assertSame(403, $response->status());
        $this->assertSame(['message' => 'Acesso negado.'], $response->getData(true));
    }
}
