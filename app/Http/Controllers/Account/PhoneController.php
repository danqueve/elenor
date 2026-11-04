<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePhoneRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PhoneController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.phone', ['user' => $request->user()]);
    }

    /**
     * Save the phone and continue where the customer was going (e.g. the
     * checkout), or to their account.
     */
    public function update(UpdatePhoneRequest $request): RedirectResponse
    {
        $request->user()->update(['phone' => $request->validated('phone')]);

        return redirect()->intended(route('account'))->with('status', 'Guardamos tu teléfono.');
    }
}
