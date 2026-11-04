<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\ProfessionalStatus;
use App\Enums\UserRole;
use App\Exceptions\GoogleLoginException;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;

class GoogleUserResolver
{
    /**
     * Find or create the customer behind a Google login.
     *
     * Accounts are matched by Google id first and by email second, so the
     * admin seeded with their email is linked the first time they sign in.
     * Matching or creating by email is only safe when Google vouches for
     * that email, so an unverified one is refused.
     *
     * Only profile data is ever written. Role, account type and professional
     * status are never touched here.
     *
     * @throws GoogleLoginException
     */
    public function resolve(AbstractUser $google): User
    {
        $googleId = (string) $google->getId();
        $email = (string) $google->getEmail();

        if ($googleId === '' || $email === '') {
            throw GoogleLoginException::missingData();
        }

        if (! $this->emailIsVerified($google)) {
            throw GoogleLoginException::unverifiedEmail();
        }

        $user = User::query()->where('google_id', $googleId)->first()
            ?? User::query()->where('email', $email)->first();

        if ($user === null) {
            return $this->create($google, $googleId, $email);
        }

        // Same email but another Google account: never hand the account over.
        if ($user->google_id !== null && $user->google_id !== $googleId) {
            throw GoogleLoginException::accountConflict();
        }

        $user->forceFill([
            'google_id' => $googleId,
            'avatar' => $this->avatar($google) ?? $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    private function create(AbstractUser $google, string $googleId, string $email): User
    {
        $user = new User([
            'name' => Str::limit($google->getName() ?: Str::before($email, '@'), 255, ''),
            'email' => $email,
            'google_id' => $googleId,
            'avatar' => $this->avatar($google),
        ]);

        // Set explicitly (not left to the column defaults) so the model is complete.
        $user->forceFill([
            'email_verified_at' => now(),
            'role' => UserRole::Customer,
            'account_type' => AccountType::FinalConsumer,
            'professional_status' => ProfessionalStatus::None,
        ])->save();

        return $user;
    }

    private function emailIsVerified(AbstractUser $google): bool
    {
        return filter_var($google->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * The profile picture, if it is a reasonable https URL.
     */
    private function avatar(AbstractUser $google): ?string
    {
        $avatar = $google->getAvatar();

        return is_string($avatar) && str_starts_with($avatar, 'https://') && strlen($avatar) <= 500
            ? $avatar
            : null;
    }
}
