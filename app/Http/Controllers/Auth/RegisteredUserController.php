<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\MemberRegistered;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->validated(),
            // Role selalu berasal dari config, JANGAN pernah dari input user:
            // registrasi publik hanya boleh membuat anggota.
            'role' => config('perpustakaan.registration.default_role', User::ROLE_ANGGOTA),
        ]);

        event(new Registered($user));

        Mail::to($user->email)->send(new MemberRegistered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('anggota.dashboard'));
    }
}
