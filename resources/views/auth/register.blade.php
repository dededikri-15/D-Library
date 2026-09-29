@extends('layouts.app')

@section('title', 'Daftar - ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">Daftar Anggota</h1>
            <p class="mt-2 text-sm text-secondary">Pendaftaran ini otomatis mendapat role anggota.</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="name" label="Nama lengkap" required autocomplete="name" />
                <x-form.input name="email" label="Email" type="email" required autocomplete="username" />
                <x-form.input name="password" label="Kata sandi" type="password" required
                              autocomplete="new-password" hint="Minimal 8 karakter." />
                <x-form.input name="password_confirmation" label="Ulangi kata sandi" type="password" required
                              autocomplete="new-password" />

                <button type="submit" class="btn btn-primary w-full">Daftar</button>
            </form>

            <p class="mt-6 text-center text-sm text-secondary">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-tertiary hover:underline">Masuk</a>
            </p>
        </div>
    </div>
@endsection
