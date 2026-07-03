<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

trait HandlesUserProfileImage
{
    protected function profileImageValidationRules(): array
    {
        if (! Schema::hasColumn('users', 'profile_image')) {
            return [];
        }

        return [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'remove_profile_image' => 'nullable|boolean',
        ];
    }

    protected function profileImagePayloadFromRequest(Request $request, ?User $user = null): array
    {
        if (! Schema::hasColumn('users', 'profile_image')) {
            return [];
        }

        if ($request->boolean('remove_profile_image')) {
            $this->deleteStoredProfileImage($user?->profile_image);

            return ['profile_image' => null];
        }

        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            $this->deleteStoredProfileImage($user?->profile_image);

            return [
                'profile_image' => $request->file('profile_image')->store('profile-images', 'public'),
            ];
        }

        return [];
    }

    protected function deleteStoredProfileImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
