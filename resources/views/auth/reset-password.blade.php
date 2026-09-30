@extends('layouts.app')

@section('title', 'Reset Sandi - ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">Reset Sandi</h1>
            <p class="mt-2 text-sm text-secondary">Buat sandi baru untuk akun anda.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5" data-submit-once>
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <x-form.input name="email" label="Email" type="email" required autocomplete="username" />

                <x-form.input name="password" label="Sandi Baru" type="password" required autocomplete="new-password" />

                <x-form.input name="password_confirmation" label="Konfirmasi Sandi" type="password" required autocomplete="new-password" />

                <button type="submit" class="btn btn-primary w-full">Reset Sandi</button>
            </form>
        </div>
    </div>
@endsection
