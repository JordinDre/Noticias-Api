<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

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

            // Enviar email de verificación
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'User registered successfully. Please check your email to verify your account.',
                'data'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
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

        $user = auth('api')->user();

        // Verificar si el email está verificado
        if (!$user->hasVerifiedEmail()) {
            auth('api')->logout();
            return response()->json([
                'message' => 'Please verify your email address before logging in.',
                'email_verified' => false,
            ], 403);
        }

        return $this->respondWithToken($token);
    }

    /**
     * GET /api/me
     */
    public function me()
    {
        return response()->json([
            'data' => auth('api')->user()->load('roles'),
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
            'user' => auth('api')->user()->load('roles'),
        ]);
    }

    /**
     * GET /api/email/verify/{id}/{hash}
     * Verifica el email del usuario
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        // Buscar el usuario
        $user = User::findOrFail($id);

        // Verificar que el hash sea válido
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link.',
            ], 400);
        }

        // Verificar si ya está verificado
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'email_verified' => true,
            ], 200);
        }

        // Marcar como verificado
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully. You can now log in.',
            'email_verified' => true,
        ], 200);
    }

    /**
     * POST /api/email/resend
     * Reenvía el email de verificación
     */
    public function resendVerificationEmail(Request $request)
    {
        $user = auth('api')->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
                'email_verified' => true,
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email resent. Please check your email.',
        ], 200);
    }

    /**
     * POST /api/password/forgot
     * Envía el enlace de recuperación de contraseña
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Enviar enlace de reset
        $status = PasswordFacade::sendResetLink(
            $request->only('email')
        );

        if ($status === PasswordFacade::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.',
            ], 200);
        }

        return response()->json([
            'message' => 'Unable to send reset link.',
        ], 500);
    }

    /**
     * POST /api/password/reset
     * Resetea la contraseña usando el token
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $status = PasswordFacade::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === PasswordFacade::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successfully. You can now log in with your new password.',
            ], 200);
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }
}