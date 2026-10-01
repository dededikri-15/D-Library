@extends('layouts.app')

@section('title', 'Reset Sandi - ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.reset_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.reset_description') }}</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5" data-submit-once>
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />

                <x-form.input name="password" :label="__('auth.new_password')" type="password" required autocomplete="new-password" />

                <x-form.input name="password_confirmation" :label="__('auth.confirm_new_password')" type="password" required autocomplete="new-password" />

                <button type="submit" class="btn btn-primary w-full">{{ __('auth.reset_password') }}</button>
            </form>
        </div>
    </div>
@endsection
