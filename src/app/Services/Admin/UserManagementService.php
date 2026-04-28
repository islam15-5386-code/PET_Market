<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserManagementService
{
    /**
     * Paginated list of all users with optional filters.
     */
    public function list(array $filters): LengthAwarePaginator
    {
        $query = User::withCount('orders');

        // Search by name or email (case-insensitive)
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ilike', "%{$term}%")
                  ->orWhere('email', 'ilike', "%{$term}%");
            });
        }

        // Filter by role
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Filter by account status
        if (!empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a single user with their order count.
     */
    public function find(int $id): User
    {
        return User::withCount('orders')->findOrFail($id);
    }

    /**
     * Block a user — prevents login via JwtMiddleware.
     *
     * @throws \Exception
     */
    public function block(User $target, User $admin): User
    {
        if ($target->isAdmin()) {
            throw new \Exception('Admin accounts cannot be blocked.', 422);
        }

        if (!$target->is_active) {
            throw new \Exception("'{$target->name}' is already blocked.", 422);
        }

        $target->update(['is_active' => false]);

        return $target->fresh();
    }

    /**
     * Unblock a previously blocked user.
     *
     * @throws \Exception
     */
    public function unblock(User $target): User
    {
        if ($target->is_active) {
            throw new \Exception("'{$target->name}' is not currently blocked.", 422);
        }

        $target->update(['is_active' => true]);

        return $target->fresh();
    }

    /**
     * Paginated order history for any user (admin view).
     */
    public function userOrders(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->orders()
            ->with('items')
            ->withCount('items')
            ->latest()
            ->paginate($perPage);
    }
}
