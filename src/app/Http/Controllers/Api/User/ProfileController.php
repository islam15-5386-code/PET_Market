<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    // ── GET /api/user/profile ─────────────────────────────────────────────────

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => ['user' => new UserResource($request->user())],
        ]);
    }

    // ── PUT /api/user/profile ─────────────────────────────────────────────────

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data'    => ['user' => new UserResource($user)],
        ]);
    }

    // ── POST /api/user/profile/avatar ─────────────────────────────────────────

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $user = $this->profileService->uploadAvatar(
            $request->user(),
            $request->file('avatar')
        );

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully.',
            'data'    => [
                'avatar_url' => $user->avatar_url,
                'user'       => new UserResource($user),
            ],
        ]);
    }

    // ── GET /api/user/orders ──────────────────────────────────────────────────

    public function orders(Request $request): JsonResponse
    {
        $orders = $this->profileService->orderHistory(
            $request->user(),
            (int) $request->get('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'orders' => OrderResource::collection($orders),
                'meta'   => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                ],
            ],
        ]);
    }
}
