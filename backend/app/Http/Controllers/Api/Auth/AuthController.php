<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data'    => [
                'user'  => new UserResource($result['user']),
                'token' => $this->authService->tokenResponse($result['token']),
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->email, $request->password);
            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => [
                    'user'  => new UserResource($result['user']),
                    'token' => $this->authService->tokenResponse($result['token']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return response()->json(['success' => true, 'message' => 'Successfully logged out.']);
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();
            return response()->json([
                'success' => true,
                'message' => 'Token refreshed.',
                'data'    => [
                    'user'  => new UserResource($result['user']),
                    'token' => $this->authService->tokenResponse($result['token']),
                ],
            ]);
        } catch (\Exception) {
            return response()->json(['success' => false, 'message' => 'Could not refresh token.'], 401);
        }
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => ['user' => new UserResource(auth('api')->user())],
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->email);
        return response()->json([
            'success' => true,
            'message' => 'Password reset link has been sent to your email.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }
}
