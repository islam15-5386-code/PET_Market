<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\UserFilterRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Http\Resources\OrderResource;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userService
    ) {}

    // ── GET /api/admin/users ──────────────────────────────────────────────────

    public function index(UserFilterRequest $request): JsonResponse
    {
        $users = $this->userService->list($request->validated());

        return response()->json([
            'success' => true,
            'data'    => [
                'users' => AdminUserResource::collection($users),
                'meta'  => [
                    'current_page' => $users->currentPage(),
                    'last_page'    => $users->lastPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                ],
            ],
        ]);
    }

    // ── GET /api/admin/users/{id} ─────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->find($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => ['user' => new AdminUserResource($user)],
        ]);
    }

    // ── POST /api/admin/users/{id}/block ──────────────────────────────────────

    public function block(Request $request, int $id): JsonResponse
    {
        try {
            $target = User::findOrFail($id);
            $user   = $this->userService->block($target, $request->user());
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => "'{$user->name}' has been blocked.",
            'data'    => ['user' => new AdminUserResource($user)],
        ]);
    }

    // ── POST /api/admin/users/{id}/unblock ────────────────────────────────────

    public function unblock(int $id): JsonResponse
    {
        try {
            $target = User::findOrFail($id);
            $user   = $this->userService->unblock($target);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }

        return response()->json([
            'success' => true,
            'message' => "'{$user->name}' has been unblocked.",
            'data'    => ['user' => new AdminUserResource($user)],
        ]);
    }

    // ── GET /api/admin/users/{id}/orders ──────────────────────────────────────

    public function orders(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $orders = $this->userService->userOrders(
            $user,
            (int) $request->get('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'user'   => new AdminUserResource($user),
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
