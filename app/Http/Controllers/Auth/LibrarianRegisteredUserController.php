<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LibrarianRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rules\Password;

class LibrarianRegisteredUserController extends Controller
{
    public function create(): View|RedirectResponse
    {
        // Hanya aktif ketika belum ada pustakawan sama sekali
        $hasLibrarian = User::role(User::ROLE_PUSTAKAWAN)->exists();

        if ($hasLibrarian) {
            // Sudah ada akun pustakawan, redirect ke login dengan pesan
            return redirect()->route('login')
                ->with('error', 'Registrasi pustakawan saat ini tidak aktif. Sudah ada akun staff.');
        }

        // Tampilkan form registrasi pustakawan
        return view('auth.librarian_register', [
            'hasLibrarian' => $hasLibrarian,
        ]);
    }

    public function store(LibrarianRegisterRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'password', 'secret']);

        // Normalisasi email ke lowercase
        $data['email'] = Str::lower($data['email'] ?? '');

        // Buat user dengan role PUSTAKAWAN dari konstanta model
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_PUSTAKAWAN,
            'email_verified_at' => now(), // Verifikasi email langsung untuk setup
        ]);

        // Login otomatis setelah registrasi
        Auth::login($user);

        // Regenerasi session ID
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}