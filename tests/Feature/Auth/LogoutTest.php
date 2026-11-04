<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_signing_out_ends_the_session_and_goes_home(): void
    {
        $this->actingAs(User::factory()->create());
        $oldToken = csrf_token();

        $this->post('/salir')
            ->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Cerraste sesión.');

        $this->assertGuest();
        $this->assertNotSame($oldToken, csrf_token());
    }

    public function test_signing_out_only_works_with_a_post(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/salir')->assertStatus(405);

        $this->assertAuthenticated();
    }

    public function test_a_visitor_who_is_not_signed_in_is_sent_to_the_login(): void
    {
        $this->post('/salir')->assertRedirect(route('login'));
    }

    public function test_the_notice_after_signing_out_is_shown_once(): void
    {
        $this->actingAs(User::factory()->create());

        $this->followingRedirects()->post('/salir')->assertSee('Cerraste sesión.');
        $this->get('/')->assertDontSee('Cerraste sesión.');
    }
}
