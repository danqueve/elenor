<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * By default a guest cart, tied to a browser session.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => Str::random(40),
        ];
    }

    /**
     * The cart of a logged-in user (it has no session).
     */
    public function forUser(User $user): static
    {
        return $this->state([
            'user_id' => $user->getKey(),
            'session_id' => null,
        ]);
    }
}
