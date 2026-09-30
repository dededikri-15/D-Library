@extends('layouts.app')

@section('title', 'Masuk - ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">Masuk</h1>
            <p class="mt-2 text-sm text-secondary">Gunakan akun yang sudah terdaftar untuk mengakses layanan.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="email" label="Email" type="email" required autocomplete="username" />
                <x-form.input name="password" label="Kata sandi" type="password" required autocomplete="current-password" />

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-secondary">
                        <input type="checkbox" name="remember" value="1"
                               class="rounded border-secondary/40 text-tertiary focus:ring-tertiary/30">
                        Ingat saya
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-tertiary hover:underline">Lupa sandi?</a>
                </div>

                <button type="submit" class="btn btn-primary w-full">Masuk</button>
            </form>

            @if (config('perpustakaan.registration.enabled', true))
                <p class="mt-6 text-center text-sm text-secondary">
                    Belum punya akun?
                    <a href="{{ route('register') }}" class="font-medium text-tertiary hover:underline">Daftar sebagai anggota</a>
                </p>
            @endif
        </div>
    </div>
@endsection
