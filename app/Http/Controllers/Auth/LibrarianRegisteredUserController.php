<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibrarianRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LibrarianRegisteredUserController extends Controller
{
    public function create(): View
    {
        $hasLibrarian = User::pustakawan()->exists();
        $secretConfigured = filled(config('perpustakaan.registration.librarian_registration.secret'));
        $registrationEnabled = config('perpustakaan.registration.librarian_registration.enabled', false)
            && $secretConfigured
            && ! $hasLibrarian;

        return view('auth.librarian_register', [
            'hasLibrarian' => $hasLibrarian,
            'secretConfigured' => $secretConfigured,
            'registrationEnabled' => $registrationEnabled,
        ]);
    }

    public function store(LibrarianRegisterRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'password']);
        $data['email'] = Str::lower($data['email']);

        $user = Cache::lock('registration:librarian-bootstrap', 30)->block(10, function () use ($data) {
            return DB::transaction(function () use ($data) {
                if (User::pustakawan()->exists()) {
                    throw ValidationException::withMessages([
                        'secret' => __('auth.librarian_registration_closed'),
                    ]);
                }

                return User::create([
                    ...$data,
                    'role' => User::ROLE_PUSTAKAWAN,
                    'email_verified_at' => now(),
                ]);
            });
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
