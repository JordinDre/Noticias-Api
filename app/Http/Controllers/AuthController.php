<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * POST /api/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => ['required', 'string', 'max:255', 'min:3'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols(),],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name'     => $request->string('name'),
                'email'    => $request->string('email'),
                'password' => Hash::make($request->string('password')),
            ])  ;

            $user->assignRole('admin');

            return response()->json([
                'message' => 'User registered successfully.',
                'data'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Could not register user.',
            ], 500);
        }
    }

    /**
     * POST /api/login
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * GET /api/me
     */
    public function me()
    {
        return response()->json([
            'data' => auth('api')->user(),
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * POST /api/refresh
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Respuesta estándar de token.
     */
    protected function respondWithToken(string $token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60, // en segundos
        ]);
    }
}