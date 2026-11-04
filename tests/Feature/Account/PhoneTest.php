<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneTest extends TestCase
{
    use RefreshDatabase;

    private function customerWithoutPhone(): User
    {
        return User::factory()->create(['phone' => null]);
    }

    public function test_the_phone_form_needs_a_signed_in_customer(): void
    {
        $this->get('/cuenta/telefono')->assertRedirect(route('login'));
        $this->put('/cuenta/telefono', ['phone' => '381 4123456'])->assertRedirect(route('login'));
    }

    public function test_the_form_shows_the_current_phone(): void
    {
        $this->actingAs(User::factory()->create(['phone' => '381 4123456']))
            ->get('/cuenta/telefono')
            ->assertOk()
            ->assertSee('value="381 4123456"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function validPhones(): array
    {
        return [
            'with area code' => ['381 4123456', '381 4123456'],
            'with trunk prefix' => ['0381-4123456', '0381-4123456'],
            'with country code' => ['+54 9 381 4123456', '+54 9 381 4123456'],
            'with parentheses' => ['(0381) 412-3456', '(0381) 412-3456'],
            'extra spaces are tidied' => ['  381   4123456  ', '381 4123456'],
        ];
    }

    #[DataProvider('validPhones')]
    public function test_a_valid_phone_is_saved(string $typed, string $saved): void
    {
        $user = $this->customerWithoutPhone();

        $this->actingAs($user)
            ->put('/cuenta/telefono', ['phone' => $typed])
            ->assertRedirect(route('account'))
            ->assertSessionHas('status', 'Guardamos tu teléfono.');

        $this->assertSame($saved, $user->fresh()->phone);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidPhones(): array
    {
        return [
            'empty' => ['', 'obligatorio'],
            'letters' => ['mi telefono', 'solo puede tener números'],
            'without area code' => ['4123456', 'código de área'],
            'too many digits' => ['5493814123456789', 'código de área'],
            'plus in the middle' => ['381+4123456', 'solo puede tener números'],
            'symbols' => ['381 412-3456 #12', 'solo puede tener números'],
        ];
    }

    #[DataProvider('invalidPhones')]
    public function test_an_invalid_phone_is_rejected_with_a_spanish_message(string $typed, string $message): void
    {
        $user = $this->customerWithoutPhone();

        $this->actingAs($user)
            ->from('/cuenta/telefono')
            ->put('/cuenta/telefono', ['phone' => $typed])
            ->assertRedirect('/cuenta/telefono')
            ->assertSessionHasErrors('phone');

        $this->assertStringContainsString($message, session('errors')->first('phone'));
        $this->assertNull($user->fresh()->phone);
    }

    public function test_after_saving_the_customer_continues_where_they_were_going(): void
    {
        $user = $this->customerWithoutPhone();

        $this->actingAs($user)
            ->withSession(['url.intended' => url('/pedidos')])
            ->put('/cuenta/telefono', ['phone' => '381 4123456'])
            ->assertRedirect(url('/pedidos'));
    }

    // The "phone" middleware that the checkout will use

    private function checkoutRoute(): void
    {
        Route::middleware(['web', 'auth', 'phone'])->group(function () {
            Route::get('/_checkout', fn () => 'the checkout');
            Route::post('/_checkout', fn () => 'order placed');
        });
    }

    public function test_the_middleware_lets_customers_with_a_phone_through(): void
    {
        $this->checkoutRoute();

        $this->actingAs(User::factory()->create(['phone' => '381 4123456']))
            ->get('/_checkout')
            ->assertOk()
            ->assertSee('the checkout');
    }

    public function test_the_middleware_asks_for_the_phone_and_brings_the_customer_back(): void
    {
        $this->checkoutRoute();
        $user = $this->customerWithoutPhone();

        $this->actingAs($user)
            ->get('/_checkout')
            ->assertRedirect(route('account.phone.edit'))
            ->assertSessionHas('status')
            ->assertSessionHas('url.intended', url('/_checkout'));

        $this->put('/cuenta/telefono', ['phone' => '381 4123456'])->assertRedirect(url('/_checkout'));
    }

    public function test_the_middleware_does_not_remember_a_post_as_the_page_to_come_back_to(): void
    {
        $this->checkoutRoute();

        $this->actingAs($this->customerWithoutPhone())
            ->post('/_checkout')
            ->assertRedirect(route('account.phone.edit'))
            ->assertSessionMissing('url.intended');
    }

    public function test_the_middleware_leaves_the_login_to_the_auth_middleware(): void
    {
        $this->checkoutRoute();

        $this->get('/_checkout')->assertRedirect(route('login'));
    }
}
