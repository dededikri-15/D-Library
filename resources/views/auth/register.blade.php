@extends('layouts.app')

@section('title', __('auth.register').' - '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.register_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.register_description') }}</p>

            <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="name" :label="__('auth.full_name')" required autocomplete="name" />
                <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />
                <x-form.input name="password" :label="__('auth.password')" type="password" required
                              autocomplete="new-password" :hint="__('auth.password_hint')" />
                <x-form.input name="password_confirmation" :label="__('auth.confirm_password')" type="password" required
                              autocomplete="new-password" />

                <button type="submit" class="btn btn-primary w-full">{{ __('auth.register') }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-secondary">
                {{ __('auth.has_account') }}
                <a href="{{ route('login') }}" class="font-medium text-tertiary hover:underline">{{ __('auth.login') }}</a>
            </p>
        </div>
    </div>
@endsection
