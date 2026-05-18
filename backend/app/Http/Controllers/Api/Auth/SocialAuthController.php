<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the provider's authentication page.
     * GET /auth/social/{provider}/redirect
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);
        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Handle the provider callback and issue a JWT.
     * GET /auth/social/{provider}/callback
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL'));

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            Log::error("Social auth callback failed for {$provider}: " . $e->getMessage());
            return redirect("{$frontendUrl}/social-callback?error=" . urlencode('Authentication failed. Please try again.'));
        }

        try {
            // Find or create user
            $user = $this->findOrCreateUser($socialUser, $provider);

            if (!$user->is_active) {
                return redirect("{$frontendUrl}/social-callback?error=" . urlencode('Your account has been suspended.'));
            }

            $token = JWTAuth::fromUser($user);

            return redirect("{$frontendUrl}/social-callback?token={$token}");
        } catch (\Exception $e) {
            Log::error("Social user creation failed: " . $e->getMessage());
            return redirect("{$frontendUrl}/social-callback?error=" . urlencode('Could not complete sign-in. Please try again.'));
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404, 'Provider not supported.');
        }
    }

    private function findOrCreateUser(mixed $socialUser, string $provider): User
    {
        $idField = "{$provider}_id";

        // 1. Try matching by provider ID
        $user = User::where($idField, $socialUser->getId())->first();
        if ($user) {
            return $user;
        }

        // 2. Try matching by email — link the account
        if ($socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
            if ($user) {
                $user->update([$idField => $socialUser->getId()]);
                return $user;
            }
        }

        // 3. Create a new user
        return User::create([
            'name'      => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email'     => $socialUser->getEmail(),
            'password'  => bcrypt(Str::random(32)), // random unusable password
            'role'      => 'user',
            'is_active' => true,
            $idField    => $socialUser->getId(),
            'avatar'    => $socialUser->getAvatar(),
        ]);
    }
}
