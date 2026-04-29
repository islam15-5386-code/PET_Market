<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    /**
     * Update the user's profile with only the provided fields.
     */
    public function update(User $user, array $data): User
    {
        // Filter out nulls so we don't accidentally wipe existing values
        $user->fill(array_filter($data, fn ($v) => $v !== null));
        $user->save();

        return $user->fresh();
    }

    /**
     * Store a new avatar, delete the old one, update the user record.
     */
    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        // Delete old avatar if it's a locally stored file
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store under avatars/{user_id}/filename
        $path = $file->store("avatars/{$user->id}", 'public');

        $user->update(['avatar' => $path]);

        return $user->fresh();
    }

    /**
     * Paginated order history with line items and item count.
     */
    public function orderHistory(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return $user->orders()
            ->with('items')
            ->withCount('items')
            ->latest()
            ->paginate($perPage);
    }
}
