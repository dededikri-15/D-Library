<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        /*
         * Email disimpan lowercase, sedangkan perbandingan teks di PostgreSQL
         * bersifat case-sensitive. Input dinormalkan di sini supaya pengguna
         * yang mengetik huruf besar tetap mendapat tautan reset.
         */
        if (is_string($request->input('email'))) {
            $request->merge(['email' => Str::lower($request->input('email'))]);
        }

        $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
    }
}
