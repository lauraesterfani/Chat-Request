<?php
    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Routing\Controller as RoutingController;

    class AdminAuthController extends RoutingController
    {
        public function login(Request $request)
        {
            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            // Tenta autenticar usando o guarda 'api-admin'
            if (! $token = auth('api-admin')->attempt($credentials)) {
                return response()->json(['error' => 'Credenciais de admin inválidas.'], 401);
            }

            return $this->respondWithToken($token);
        }

        protected function respondWithToken($token)
        {
            return response()->json([
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => \Tymon\JWTAuth\Facades\JWTAuth::factory()->getTTL() * 60,
                'user'         => auth('api-admin')->user(),
            ]);
        }
}
