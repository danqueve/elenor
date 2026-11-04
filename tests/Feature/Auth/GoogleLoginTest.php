<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountType;
use App\Enums\ProfessionalStatus;
use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\User;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLE_ID = '118234567890123456789';

    private const CALLBACK = '/auth/google/callback?code=abc&state=xyz';

    private function configureGoogle(): void
    {
        config(['services.google' => [
            'client_id' => 'client-123',
            'client_secret' => 'secret-xyz',
            'redirect' => 'http://localhost:8000/auth/google/callback',
        ]]);
    }

    /**
     * Pretend Google answered the callback with this profile. Socialite is
     * mocked, so nothing leaves the test.
     *
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $raw  What Google's userinfo returns besides the profile.
     */
    private function googleReturns(array $profile = [], array $raw = []): void
    {
        $google = (new SocialiteUser)
            ->setRaw($raw + ['email_verified' => true])
            ->map($profile + [
                'id' => self::GOOGLE_ID,
                'name' => 'Marcela Gómez',
                'email' => 'marcela@example.com',
                'avatar' => 'https://lh3.googleusercontent.com/a/marcela',
            ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($google);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function hasRememberCookie(TestResponse $response): bool
    {
        return collect($response->headers->getCookies())
            ->contains(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
    }

    // The way out to Google

    public function test_the_redirect_sends_the_visitor_to_google_with_a_state(): void
    {
        $this->configureGoogle();

        $response = $this->get('/auth/google/redirect');

        $response->assertRedirectContains('https://accounts.google.com/o/oauth2/auth');

        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('client-123', $query['client_id']);
        $this->assertSame('http://localhost:8000/auth/google/callback', $query['redirect_uri']);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('openid profile email', $query['scope']);
        $this->assertSame('select_account', $query['prompt']);
        $this->assertNotEmpty($query['state']);
        $this->assertSame($query['state'], session('state'));
    }

    public function test_the_redirect_explains_when_google_is_not_configured(): void
    {
        Exceptions::fake();
        config(['services.google.client_id' => null]);

        $this->get('/auth/google/redirect')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        Exceptions::assertReported(RuntimeException::class);
    }

    // The way back

    public function test_a_new_customer_is_created_and_signed_in(): void
    {
        $this->googleReturns();

        $this->get(self::CALLBACK)->assertRedirect(route('account.phone.edit'));

        $user = User::query()->where('email', 'marcela@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(self::GOOGLE_ID, $user->google_id);
        $this->assertSame('Marcela Gómez', $user->name);
        $this->assertSame('https://lh3.googleusercontent.com/a/marcela', $user->avatar);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertSame(AccountType::FinalConsumer, $user->account_type);
        $this->assertSame(ProfessionalStatus::None, $user->professional_status);
    }

    public function test_a_returning_customer_with_a_phone_goes_to_their_account(): void
    {
        $user = User::factory()->create([
            'google_id' => self::GOOGLE_ID,
            'email' => 'marcela@example.com',
            'phone' => '+54 381 555-1234',
            'avatar' => 'https://lh3.googleusercontent.com/a/old',
        ]);
        $this->googleReturns();

        $this->get(self::CALLBACK)->assertRedirect(route('account'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::query()->count());
        $this->assertSame('https://lh3.googleusercontent.com/a/marcela', $user->fresh()->avatar);
    }

    public function test_the_seeded_admin_is_linked_by_email_and_keeps_the_role(): void
    {
        $admin = User::factory()->admin()->unverified()->create([
            'email' => 'marcela@example.com',
            'google_id' => null,
            'phone' => '+54 381 555-1234',
        ]);
        $this->googleReturns();

        $this->get(self::CALLBACK)->assertRedirect(route('account'));

        $admin = $admin->fresh();

        $this->assertAuthenticatedAs($admin);
        $this->assertSame(self::GOOGLE_ID, $admin->google_id);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertSame(1, User::query()->count());
    }

    public function test_an_email_google_has_not_verified_is_refused(): void
    {
        $this->googleReturns(raw: ['email_verified' => false]);

        $this->get(self::CALLBACK)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_login_that_does_not_say_the_email_is_verified_is_refused(): void
    {
        $this->googleReturns(raw: ['email_verified' => null]);

        $this->get(self::CALLBACK)->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_an_email_that_belongs_to_another_google_account_is_refused(): void
    {
        $owner = User::factory()->create(['email' => 'marcela@example.com', 'google_id' => 'another-google-account']);
        $this->googleReturns();

        $this->get(self::CALLBACK)->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame('another-google-account', $owner->fresh()->google_id);
    }

    public function test_a_google_profile_without_email_is_refused(): void
    {
        $this->googleReturns(['email' => null]);

        $this->get(self::CALLBACK)->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_cancelling_on_googles_screen_comes_back_with_a_friendly_message(): void
    {
        $this->followingRedirects()
            ->get('/auth/google/callback?error=access_denied')
            ->assertSee('cancelaste el acceso con Google');

        $this->assertGuest();
    }

    public function test_any_other_error_from_google_is_refused_without_details(): void
    {
        $this->followingRedirects()
            ->get('/auth/google/callback?error=server_error')
            ->assertSee('No pudimos ingresar con Google')
            ->assertDontSee('server_error');

        $this->assertGuest();
    }

    public function test_a_callback_with_a_forged_state_is_refused(): void
    {
        Exceptions::fake();
        $this->configureGoogle();

        $this->get('/auth/google/callback?code=abc&state=forged')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertSame(0, User::query()->count());
    }

    public function test_a_failure_talking_to_google_is_refused_without_details(): void
    {
        Exceptions::fake();

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow(new ConnectException('cURL error 6: secret internals', new Request('POST', 'https://oauth2.googleapis.com/token')));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->followingRedirects()
            ->get(self::CALLBACK)
            ->assertSee('No pudimos ingresar con Google')
            ->assertDontSee('secret internals');

        $this->assertGuest();
    }

    // What happens on a successful login

    public function test_the_session_id_changes_and_the_guest_cart_comes_along(): void
    {
        $guestSessionId = Str::random(40);
        $guestCart = Cart::factory()->create(['session_id' => $guestSessionId]);
        CartItem::factory()->create(['cart_id' => $guestCart->id, 'quantity' => '3.00']);
        $this->googleReturns();

        $this->withCookie(config('session.cookie'), $guestSessionId)->get(self::CALLBACK);

        $user = User::query()->where('email', 'marcela@example.com')->firstOrFail();
        $cart = Cart::query()->where('user_id', $user->id)->first();

        $this->assertNotSame($guestSessionId, session()->getId(), 'The session id must change on login.');
        $this->assertNotNull($cart, 'The guest cart was not handed to the customer.');
        $this->assertSame($guestCart->id, $cart->id);
        $this->assertNull($cart->session_id);
    }

    public function test_customers_are_remembered_but_staff_are_not(): void
    {
        User::factory()->create(['google_id' => self::GOOGLE_ID, 'email' => 'marcela@example.com', 'phone' => '+54 381 555-1234']);
        $this->googleReturns();

        $this->assertTrue($this->hasRememberCookie($this->get(self::CALLBACK)));

        $this->post(route('logout'));
        User::query()->delete();

        User::factory()->admin()->create(['google_id' => self::GOOGLE_ID, 'email' => 'marcela@example.com', 'phone' => '+54 381 555-1234']);
        $this->googleReturns();

        $this->assertFalse($this->hasRememberCookie($this->get(self::CALLBACK)));
    }

    public function test_a_remembered_customer_comes_back_without_a_session(): void
    {
        $user = User::factory()->create(['google_id' => self::GOOGLE_ID, 'email' => 'marcela@example.com', 'phone' => '+54 381 555-1234']);
        $this->googleReturns();

        $rememberCookie = collect($this->get(self::CALLBACK)->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        // A new browser visit: no session, only the remember cookie from before.
        $this->flushSession();
        Auth::forgetGuards();

        $this->withUnencryptedCookie($rememberCookie->getName(), $rememberCookie->getValue())
            ->get('/cuenta')
            ->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_customer_returns_to_the_page_they_wanted(): void
    {
        User::factory()->create(['google_id' => self::GOOGLE_ID, 'email' => 'marcela@example.com', 'phone' => '+54 381 555-1234']);
        $this->googleReturns();

        $this->withSession(['url.intended' => url('/pedidos')])
            ->get(self::CALLBACK)
            ->assertRedirect(url('/pedidos'));
    }

    public function test_without_a_phone_the_wanted_page_is_kept_for_after_saving_it(): void
    {
        $this->googleReturns();

        $this->withSession(['url.intended' => url('/pedidos')])
            ->get(self::CALLBACK)
            ->assertRedirect(route('account.phone.edit'))
            ->assertSessionHas('url.intended', url('/pedidos'));
    }

    // The pages

    public function test_the_login_page_offers_google(): void
    {
        $this->get('/ingresar')
            ->assertOk()
            ->assertSee('Continuar con Google')
            ->assertSee('href="'.route('auth.google.redirect').'"', false);
    }

    public function test_signed_in_visitors_are_sent_to_their_account_instead_of_the_login(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/ingresar')->assertRedirect(route('account'));
        $this->get('/auth/google/redirect')->assertRedirect(route('account'));
    }
}
