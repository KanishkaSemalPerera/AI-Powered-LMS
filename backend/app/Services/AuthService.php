<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Account and token lifecycle: registration, login, logout and password changes.
 */
class AuthService
{
    private const TOKEN_NAME = 'spa';

    /**
     * @param  array{name: string, email: string, password: string, role: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        // Role is not mass assignable; only student/instructor pass validation.
        $user->forceFill(['role' => UserRole::from($data['role']), 'last_login_at' => now()])->save();

        event(new Registered($user));

        return ['user' => $user, 'token' => $this->issueToken($user)];
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException when the credentials are wrong
     * @throws AuthorizationException when the account is deactivated
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        // Same message for unknown email and wrong password, so accounts can't be enumerated.
        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            throw new AuthorizationException('Your account has been deactivated. Please contact support.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return ['user' => $user, 'token' => $this->issueToken($user)];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Changes the password and signs out every other device.
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => $newPassword])->save();

        $current = $user->currentAccessToken();
        $currentTokenId = $current instanceof PersonalAccessToken ? $current->id : null;
        $user->tokens()->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))->delete();
    }

    /**
     * Sends a reset link. Always reports success to the caller, so the response
     * never reveals whether an email address is registered.
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * @throws ValidationException when the token is invalid or expired
     */
    public function resetPassword(array $credentials): void
    {
        $status = Password::reset($credentials, function (User $user, string $password) {
            $user->forceFill(['password' => $password])->save();

            // A reset means the old password may be compromised: sign out everywhere.
            $user->tokens()->delete();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }

    private function issueToken(User $user): string
    {
        return $user->createToken(self::TOKEN_NAME)->plainTextToken;
    }
}
