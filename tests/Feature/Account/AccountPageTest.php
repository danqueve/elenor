<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_who_is_not_signed_in_is_sent_to_the_login(): void
    {
        $this->get('/cuenta')->assertRedirect(route('login'));
    }

    public function test_the_account_shows_the_customers_data_and_a_logout_form(): void
    {
        $user = User::factory()->create([
            'name' => 'Marcela Gómez',
            'email' => 'marcela@example.com',
            'phone' => '+54 381 555-1234',
        ]);

        $this->actingAs($user)
            ->get('/cuenta')
            ->assertOk()
            ->assertSee('Marcela Gómez')
            ->assertSee('marcela@example.com')
            ->assertSee('+54 381 555-1234')
            ->assertSee('Cambiar')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Cerrar sesión');
    }

    public function test_a_missing_phone_is_pointed_out(): void
    {
        $this->actingAs(User::factory()->create(['phone' => null]))
            ->get('/cuenta')
            ->assertSee('Falta cargarlo')
            ->assertSee('Cargar');
    }

    public function test_the_header_shows_the_customer_instead_of_the_login(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Marcela Gómez', 'avatar' => null]))
            ->get('/')
            ->assertSee('Marcela')
            ->assertSee('MG')
            ->assertDontSee('Ingresar');
    }

    public function test_the_google_picture_is_shown_without_sending_our_address_to_google(): void
    {
        $this->actingAs(User::factory()->create(['avatar' => 'https://lh3.googleusercontent.com/a/marcela']))
            ->get('/cuenta')
            ->assertSee('src="https://lh3.googleusercontent.com/a/marcela"', false)
            ->assertSee('referrerpolicy="no-referrer"', false);
    }

    public function test_without_a_picture_only_the_initials_are_shown(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Marcela Gómez', 'avatar' => null]))
            ->get('/cuenta')
            ->assertSee('MG')
            ->assertDontSee('googleusercontent.com');
    }
}
