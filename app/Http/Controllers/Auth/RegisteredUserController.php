<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Mail\MemberRegistered;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
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
        // `avatar` HARUS dikeluarkan dari `validated()` dan ditangani terpisah.
        // `validated()` mengembalikan objek UploadedFile apa adanya, dan kalau
        // objek itu ikut ke `User::create()`, Eloquent akan mencoba menulisnya
        // ke kolom string — hasilnya error yang membingungkan ("could not be
        // converted to string"), bukan pesan validasi yang jelas.
        $data = $request->safe()->except(['avatar']);

        /** @var UploadedFile|null $avatar */
        $avatar = $request->file('avatar');

        $user = User::create([
            ...$data,
            // Role selalu berasal dari config, JANGAN pernah dari input user:
            // registrasi publik hanya boleh membuat anggota.
            'role' => config('perpustakaan.registration.default_role', User::ROLE_ANGGOTA),
            // Foto profil opsional. Kalau tidak ada berkas, kolom dibiarkan
            // kosong dan user memakai avatar huruf.
            'avatar' => $avatar?->store('avatars', User::avatarDisk()),
        ]);

        event(new Registered($user));

        Mail::to($user->email)->send(new MemberRegistered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('anggota.dashboard'));
    }
}
