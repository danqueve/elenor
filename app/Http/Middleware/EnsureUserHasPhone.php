<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Routes that need a way to reach the customer (the checkout) use this
 * middleware: signed-in customers without a phone are sent to the phone form
 * and come back to the page they were on once they save it.
 */
class EnsureUserHasPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || filled($user->phone)) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return redirect()
            ->route('account.phone.edit')
            ->with('status', 'Necesitamos un teléfono de contacto para coordinar tu pedido.');
    }
}
