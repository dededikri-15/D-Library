@extends('layouts.app')

@section('title', __('auth.login').' - '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.login_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.login_description') }}</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />
                <x-form.input name="password" :label="__('auth.password')" type="password" required
                              autocomplete="current-password" />

                <div class="flex items-center justify-between gap-4 text-sm">
                    <label for="remember" class="inline-flex cursor-pointer items-center gap-2 text-secondary">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))
                               class="rounded-sm border-secondary/30 text-tertiary focus:ring-tertiary/30">
                        {{ __('auth.remember_me') }}
                    </label>

                    <a href="{{ route('password.request') }}" class="font-medium text-tertiary hover:underline">
                        {{ __('auth.forgot_password') }}
                    </a>
                </div>

                <button type="submit" class="btn btn-primary w-full">{{ __('auth.login') }}</button>
            </form>

            @if (config('perpustakaan.registration.enabled', true))
                <p class="mt-6 text-center text-sm text-secondary">
                    {{ __('auth.no_account') }}
                    <a href="{{ route('register') }}" class="font-medium text-tertiary hover:underline">
                        {{ __('auth.join_as_member') }}
                    </a>
                </p>
            @endif
        </div>
    </div>
@endsection
