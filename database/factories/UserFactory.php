<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\ProfessionalStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Google account ids are numeric strings of about 21 digits.
            'google_id' => fake()->unique()->numerify('#####################'),
            'avatar' => null,
            'phone' => '+54 381 '.fake()->numerify('###-####'),
            'account_type' => AccountType::FinalConsumer,
            'professional_status' => ProfessionalStatus::None,
            'role' => UserRole::Customer,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin]);
    }

    public function sales(): static
    {
        return $this->state(['role' => UserRole::Sales]);
    }

    public function warehouse(): static
    {
        return $this->state(['role' => UserRole::Warehouse]);
    }

    /**
     * A professional (e.g. an electrician) whose account was approved.
     */
    public function professional(): static
    {
        return $this->state([
            'account_type' => AccountType::Professional,
            'professional_status' => ProfessionalStatus::Approved,
        ]);
    }

    /**
     * A company account that was approved.
     */
    public function company(): static
    {
        return $this->state([
            'account_type' => AccountType::Company,
            'professional_status' => ProfessionalStatus::Approved,
        ]);
    }

    /**
     * A professional account waiting for an admin to approve it.
     */
    public function pendingProfessional(): static
    {
        return $this->state([
            'account_type' => AccountType::Professional,
            'professional_status' => ProfessionalStatus::Pending,
        ]);
    }
}
