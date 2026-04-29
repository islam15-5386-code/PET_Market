<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->unauthorized('User not found.');
            }

            if (!$user->is_active) {
                return $this->unauthorized('Your account has been suspended.');
            }

        } catch (TokenExpiredException) {
            return $this->unauthorized('Token has expired.', 'token_expired');
        } catch (TokenInvalidException) {
            return $this->unauthorized('Token is invalid.', 'token_invalid');
        } catch (JWTException) {
            return $this->unauthorized('Token is missing.', 'token_absent');
        }

        return $next($request);
    }

    private function unauthorized(string $message, string $code = 'unauthorized'): Response
    {
        return response()->json([
            'success' => false,
            'code'    => $code,
            'message' => $message,
        ], 401);
    }
}
