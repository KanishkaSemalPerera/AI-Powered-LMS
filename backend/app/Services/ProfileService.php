<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        $user->fill($data);

        // A changed email address has not been verified yet.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }

    /**
     * Stores a new avatar on the configured disk and removes the previous file.
     */
    public function updateAvatar(User $user, UploadedFile $file): User
    {
        $disk = Storage::disk(config('filesystems.default'));
        $previous = $user->avatar_path;

        $path = $file->store("avatars/{$user->id}", config('filesystems.default'));
        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous && $previous !== $path) {
            $disk->delete($previous);
        }

        return $user;
    }

    public function removeAvatar(User $user): User
    {
        if ($user->avatar_path) {
            Storage::disk(config('filesystems.default'))->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return $user;
    }
}
