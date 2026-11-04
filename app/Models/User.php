<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\ProfessionalStatus;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

// Only what comes from the Google profile (plus the phone the customer types)
// is mass assignable. Role, account type and professional status are never
// set from request data.
#[Fillable(['name', 'email', 'google_id', 'avatar', 'phone'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'account_type' => AccountType::class,
            'professional_status' => ProfessionalStatus::class,
            'role' => UserRole::class,
        ];
    }

    /** @return HasMany<Address, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /** @return HasOne<FiscalData, $this> */
    public function fiscalData(): HasOne
    {
        return $this->hasOne(FiscalData::class);
    }

    /** @return HasOne<Cart, $this> */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Professional prices apply to professional and company accounts once an
     * admin approved them. Everyone else pays the list price.
     */
    public function hasProfessionalPricing(): bool
    {
        return $this->account_type !== AccountType::FinalConsumer
            && $this->professional_status === ProfessionalStatus::Approved;
    }

    /**
     * There is no password column: Google is the only way in. Laravel still
     * asks for the password hash on every login (to sign the "remember me"
     * cookie), and strict models refuse to read a column that doesn't exist.
     * The remember cookie stays protected by the random remember_token.
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * "Marcela Gómez" => "Marcela".
     */
    public function firstName(): string
    {
        return Str::of($this->name)->trim()->before(' ')->toString();
    }

    /**
     * Two letters for the avatar when there is no profile picture:
     * "Marcela Gómez" => "MG".
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];

        $last = count($words) > 1 ? end($words) : '';

        return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($last, 0, 1));
    }
}
