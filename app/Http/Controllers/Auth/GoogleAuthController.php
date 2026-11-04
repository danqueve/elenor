<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Exceptions\GoogleLoginException;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\GoogleUserResolver;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use RuntimeException;

class GoogleAuthController extends Controller
{
    /**
     * Send the visitor to Google. Socialite adds a random "state" kept in the
     * session, which the callback checks: that is the CSRF protection of the flow.
     */
    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            report(new RuntimeException('Google login is not configured: set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET and GOOGLE_REDIRECT_URI.'));

            return $this->failed('El ingreso con Google todavía no está disponible. Probá de nuevo más tarde.');
        }

        // select_account: always show Google's account chooser, so people (and
        // staff) with several Google accounts pick the right one.
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    /**
     * Google sends the visitor back here with a one-time code. Socialite
     * exchanges it for the user's data on the server, never in the browser.
     */
    public function callback(Request $request, GoogleUserResolver $users, CartService $carts): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->failed($request->input('error') === 'access_denied'
                ? 'No pudimos ingresar porque cancelaste el acceso con Google. Si querés, probá de nuevo.'
                : 'No pudimos ingresar con Google. Probá de nuevo en un momento.');
        }

        try {
            $user = $users->resolve(Socialite::driver('google')->user());
        } catch (GoogleLoginException $exception) {
            return $this->failed($exception->getMessage());
        } catch (InvalidStateException|GuzzleException $exception) {
            // Lost session (expired or opened the callback directly) or Google didn't answer.
            report($exception);

            return $this->failed('No pudimos ingresar con Google. Probá de nuevo en un momento.');
        }

        // Guest carts are keyed by session id and the login regenerates it: read it first.
        $guestSessionId = $request->session()->getId();

        // Customers stay signed in; staff sign in again when the session expires.
        Auth::login($user, remember: $user->role === UserRole::Customer);
        $request->session()->regenerate();

        $carts->mergeGuestCart($guestSessionId, $user);

        // First time: ask for the phone right away. The page the customer wanted
        // (e.g. the checkout) stays saved and is opened after the phone is saved.
        if (blank($user->phone)) {
            return redirect()->route('account.phone.edit');
        }

        return redirect()->intended(route('account'));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}
