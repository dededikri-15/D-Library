@extends('layouts.app')

@section('title', __('auth.librarian_register_title').' - '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.librarian_register_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.librarian_register_description') }}</p>

            @if ($hasLibrarian)
                <x-alert variant="info" class="mt-6">
                    {{ __('auth.librarian_registration_has_librarian') }}
                </x-alert>
            @elseif (! $registrationEnabled)
                <x-alert variant="info" class="mt-6">
                    {{ __('auth.librarian_registration_disabled') }}
                    @if (! $secretConfigured)
                        {{ __('auth.librarian_registration_setup_required') }}
                    @endif
                </x-alert>
            @else
                <form method="POST" action="{{ route('librarian.register.store') }}"
                      class="mt-8 space-y-5" data-submit-once>
                    @csrf

                    <x-form.input name="name" :label="__('auth.full_name')" required autocomplete="name" />
                    <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />
                    <x-form.input name="password" :label="__('auth.password')" type="password" required
                                  autocomplete="new-password" :hint="__('auth.password_hint')" />
                    <x-form.input name="password_confirmation" :label="__('auth.confirm_password')" type="password" required
                                  autocomplete="new-password" />

                    <div>
                        <label for="secret" class="field-label">{{ __('auth.librarian_secret') }} *</label>
                        <input id="secret" name="secret" type="password" required autocomplete="off"
                               class="field-input @if ($errors->has('secret')) border-overdue @endif">
                        @error('secret')
                            <p class="mt-1 text-label text-overdue">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-label text-secondary">{{ __('auth.librarian_secret_hint') }}</p>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        {{ __('auth.librarian_register_button') }}
                    </button>
                </form>
            @endif

            <p class="mt-6 text-center text-sm text-secondary">
                {{ __('auth.librarian_register_member_prompt') }}
                <a href="{{ route('register') }}" class="font-medium text-tertiary hover:underline">
                    {{ __('auth.register_title') }}
                </a>
            </p>
        </div>
    </div>
@endsection
