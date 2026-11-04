@extends('layouts.app')

@section('title', __('auth.librarian_register_title').' - '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.librarian_register_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.librarian_register_description') }}</p>

            <form method="POST" action="{{ route('register.librarian.store') }}"
                  enctype="multipart/form-data" class="mt-8 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="name" :label="__('auth.full_name')" required autocomplete="name" />
                <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />
                <x-form.select name="gender" :label="__('auth.gender')" required :allowEmpty="true"
                               :emptyLabel="__('users.choose_gender')" :options="App\Models\User::genderOptions()" />
                <x-form.input name="date_of_birth" :label="__('auth.date_of_birth')" type="date"
                              max="{{ now()->format('Y-m-d') }}" autocomplete="bday" />
                <x-form.input name="password" :label="__('auth.password')" type="password" required
                              autocomplete="new-password" :hint="__('auth.password_hint')" />
                <x-form.input name="password_confirmation" :label="__('auth.confirm_password')" type="password" required
                              autocomplete="new-password" />

                <div>
                    <label for="avatar" class="field-label">{{ __('auth.photo_profile') }}</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*"
                           @class([
                               'field-input file:mr-3 file:rounded-sm file:border-0 file:bg-tertiary/10 file:px-3 file:py-1.5 file:text-label file:font-medium file:text-tertiary',
                               'border-overdue' => $errors->has('avatar'),
                           ])>
                    <p class="mt-1 text-label text-secondary">{{ __('auth.photo_profile_hint') }}</p>
                    @error('avatar')
                        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">{{ __('auth.librarian_register_submit') }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-secondary">
                <a href="{{ route('users.index') }}" class="font-medium text-tertiary hover:underline">
                    {{ __('users.index_title') }}
                </a>
            </p>
        </div>
    </div>
@endsection
