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
    public function googleRedirect(): RedirectResponse
    {
        return $this->redirect('google');
    }

    public function googleCallback(): RedirectResponse
    {
        return $this->callback('google');
    }

    /**
     * Redirect the user to the provider's authentication page.
     * GET /auth/social/{provider}/redirect
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        if (!$this->isProviderConfigured($provider)) {
            return redirect($this->frontendErrorUrl('Google login is not configured yet.'));
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * Handle the provider callback and issue a JWT.
     * GET /auth/social/{provider}/callback
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
        $callbackPath = $provider === 'google' ? '/auth/google/callback' : '/social-callback';

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            Log::error("Social auth callback failed for {$provider}: " . $e->getMessage());
            return redirect("{$frontendUrl}{$callbackPath}?error=" . urlencode('Authentication failed. Please try again.'));
        }

        try {
            if (!$socialUser->getEmail()) {
                return redirect("{$frontendUrl}{$callbackPath}?error=" . urlencode('No email received from Google account.'));
            }

            // Find or create user
            $user = $this->findOrCreateUser($socialUser, $provider);

            if (!$user->is_active) {
                return redirect("{$frontendUrl}{$callbackPath}?error=" . urlencode('Your account has been suspended.'));
            }

            $token = JWTAuth::fromUser($user);

            return redirect("{$frontendUrl}{$callbackPath}?token={$token}");
        } catch (\Exception $e) {
            Log::error("Social user creation failed: " . $e->getMessage());
            return redirect("{$frontendUrl}{$callbackPath}?error=" . urlencode('Could not complete sign-in. Please try again.'));
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function validateProvider(string $provider): void
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            abort(404, 'Provider not supported.');
        }
    }

    private function isProviderConfigured(string $provider): bool
    {
        $clientId = (string) config("services.{$provider}.client_id");
        $clientSecret = (string) config("services.{$provider}.client_secret");
        $redirectUri = (string) config("services.{$provider}.redirect");

        return $clientId !== '' && $clientSecret !== '' && $redirectUri !== '';
    }

    private function frontendErrorUrl(string $message): string
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
        return "{$frontendUrl}/login?error=" . urlencode($message);
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
                $payload = [$idField => $socialUser->getId()];
                if (!$user->email_verified_at) {
                    $payload['email_verified_at'] = now();
                }
                if (!$user->avatar && $socialUser->getAvatar()) {
                    $payload['avatar'] = $socialUser->getAvatar();
                }
                $user->update($payload);
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
            'email_verified_at' => now(),
        ]);
    }
}
