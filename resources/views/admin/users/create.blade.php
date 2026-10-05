@extends('layouts.app')

@section('title', __('users.create_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('users.create_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('users.create_description') }}</p>

    <form method="POST" action="{{ route('users.store') }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf

        <x-form.input name="name" :label="__('users.name')" required :value="$user->name" />
        <x-form.input name="email" :label="__('users.email')" type="email" required :value="$user->email" autocomplete="email" />
        <x-form.select name="gender" :label="__('users.gender')" required :allowEmpty="true"
                       :emptyLabel="__('users.choose_gender')" :options="App\Models\User::genderOptions()"
                       :value="$user->gender" />
        <x-form.input name="password" :label="__('users.password')" type="password" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" :label="__('users.confirm_password')" type="password" required
                      autocomplete="new-password" />

        <x-form.select name="role" :label="__('users.role')" required :allowEmpty="false"
                       :options="collect(App\Models\User::roles())
                            ->mapWithKeys(fn ($role) => [$role => __('roles.'.$role)])
                            ->all()"
                       :value="App\Models\User::ROLE_ANGGOTA" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('users.save') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('users.cancel') }}</a>
        </div>
    </form>
@endsection
