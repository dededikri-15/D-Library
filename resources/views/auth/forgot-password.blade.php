@extends('layouts.app')

@section('title', 'Lupa Sandi - ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.forgot_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.forgot_description') }}</p>

            @if (session('status'))
                <div class="mt-4 rounded-lg bg-success/10 p-3 text-sm text-success">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />

                <button type="submit" class="btn btn-primary w-full">{{ __('auth.send_reset_link') }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-secondary">
                <a href="{{ route('login') }}" class="font-medium text-tertiary hover:underline">{{ __('auth.back_to_login') }}</a>
            </p>
        </div>
    </div>
@endsection
