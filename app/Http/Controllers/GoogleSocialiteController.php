<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Laravel\Socialite\Facades\Socialite;

class GoogleSocialiteController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback()
    {
        $socialiteUser = Socialite::driver('google')->stateless()->user();
        $user = User::firstOrCreate(
            ['email' => $socialiteUser->getEmail()],
            ['name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: 'Usuario', 'password' => bcrypt(Str::random(32))]
        );
        
        if (!$user->google_id) {
            $user->google_id = $socialiteUser->getId();
            $user->save();
        }
        $token = JWTAuth::fromUser($user);
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
        return redirect()->to($frontendUrl . '/oauth/callback?token=' . $token);
    }
}
