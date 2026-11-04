<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_logged_in_customer_sees_their_first_name_instead_of_login(): void
    {
        $user = User::factory()->create(['name' => 'Marcela Gómez']);

        $this->actingAs($user)
            ->get('/')
            ->assertSee('Marcela')
            ->assertDontSee('Gómez')
            ->assertDontSee('Ingresar');
    }

    public function test_the_account_tab_stays_active_in_every_account_page(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['/cuenta', '/cuenta/telefono'] as $url) {
            $this->get($url)->assertSeeInOrder([
                'href="'.route('account').'"',
                'aria-current="page"',
            ], false);
        }
    }

    public function test_the_mobile_menu_account_tab_points_to_the_account_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertSee('href="'.route('account').'"', false);
    }
}
