<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(array $data): array
    {
        $user  = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => 'user',
        ]);
        $token = JWTAuth::fromUser($user);
        return ['user' => $user, 'token' => $token];
    }

    /**
     * @throws \Exception
     */
    public function login(string $email, string $password): array
    {
        if (!$token = auth('api')->attempt(['email' => $email, 'password' => $password])) {
            throw new \Exception('Invalid credentials.', 401);
        }

        $user = auth('api')->user();

        if (!$user->is_active) {
            auth('api')->logout();
            throw new \Exception('Your account has been suspended. Please contact support.', 403);
        }

        return ['user' => $user, 'token' => $token];
    }

    public function logout(): void
    {
        auth('api')->logout(true);
    }

    public function refresh(): array
    {
        $token = auth('api')->refresh();
        return ['user' => auth('api')->user(), 'token' => $token];
    }

    public function sendPasswordResetLink(string $email): void
    {
        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );
        Mail::to($email)->send(new \App\Mail\PasswordResetMail($token, $email));
    }

    /**
     * @throws \Exception
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record) {
            throw new \Exception('Invalid or expired reset token.', 400);
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new \Exception('Password reset token has expired.', 400);
        }

        if (!Hash::check($token, $record->token)) {
            throw new \Exception('Invalid or expired reset token.', 400);
        }

        User::where('email', $email)->update(['password' => Hash::make($password)]);
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function tokenResponse(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
