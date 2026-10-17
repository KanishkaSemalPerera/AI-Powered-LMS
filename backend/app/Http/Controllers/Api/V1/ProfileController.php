<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly AuthService $auth,
    ) {}

    /** PUT /profile */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profiles->update($request->user(), $request->validated());

        return ApiResponse::success(new UserResource($user), 'Profile updated.');
    }

    /** POST /profile/avatar (multipart/form-data, field "avatar") */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $this->profiles->updateAvatar($request->user(), $request->file('avatar'));

        return ApiResponse::success(new UserResource($user), 'Profile photo updated.');
    }

    /** DELETE /profile/avatar */
    public function destroyAvatar(Request $request): JsonResponse
    {
        $user = $this->profiles->removeAvatar($request->user());

        return ApiResponse::success(new UserResource($user), 'Profile photo removed.');
    }

    /** PUT /profile/password */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->auth->changePassword($request->user(), $request->validated('password'));

        return ApiResponse::success(null, 'Password changed. Other devices have been signed out.');
    }
}
