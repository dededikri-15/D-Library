<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Ganti ID session agar tidak bisa di-hijack lewat session fixation.
        $request->session()->regenerate();

        $intended = $request->query('redirect');

        if ($intended !== null && $this->isSafeRedirect($intended)) {
            return redirect()->intended($intended);
        }

        return redirect()->intended($this->homeFor($request->user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Halaman tujuan setelah login, ditentukan oleh role.
     */
    protected function homeFor(?User $user): string
    {
        if ($user === null) {
            return route('home');
        }

        if ($user->isStaff()) {
            return route('dashboard');
        }

        return route('anggota.dashboard');
    }

    /**
     * Cegah open redirect: hanya izinkan path internal yang dimulai dengan "/"
     * dan BUKAN "//host" (yang akan ditafsirkan sebagai URL absolut).
     */
    protected function isSafeRedirect(mixed $target): bool
    {
        return is_string($target)
            && str_starts_with($target, '/')
            && ! str_starts_with($target, '//');
    }
}
