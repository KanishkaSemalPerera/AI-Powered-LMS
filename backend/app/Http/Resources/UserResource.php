<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isSelfOrAdmin = $viewer && ($viewer->id === $this->id || $viewer->isAdmin());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'headline' => $this->headline,
            'bio' => $this->bio,
            'avatar_url' => $this->avatarUrl(),

            // Private details only for the account owner and admins.
            'email' => $this->when($isSelfOrAdmin, $this->email),
            'email_verified' => $this->when($isSelfOrAdmin, $this->email_verified_at !== null),
            'is_active' => $this->when($isSelfOrAdmin, $this->is_active),
            'last_login_at' => $this->when($isSelfOrAdmin, fn () => $this->last_login_at?->toIso8601String()),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
