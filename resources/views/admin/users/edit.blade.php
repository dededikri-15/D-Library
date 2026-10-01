@extends('layouts.app')

@section('title', __('users.edit_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('users.edit_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $user->email }}</p>

    <form method="POST" action="{{ route('users.update', $user) }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" :label="__('users.name')" required :value="$user->name" />
        <x-form.input name="email" :label="__('users.email')" type="email" required :value="$user->email" autocomplete="email" />

        <x-form.select name="role" :label="__('users.role')" required :allowEmpty="false"
                       :options="collect(App\Models\User::roles())
                            ->mapWithKeys(fn ($role) => [$role => __('roles.'.$role)])
                            ->all()"
                       :value="$user->role" />

        <div class="rounded-lg border border-secondary/20 p-4">
            <p class="text-sm font-medium text-primary">{{ __('users.change_password') }}</p>
            <p class="mt-1 text-xs text-secondary">{{ __('users.leave_password_blank') }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-form.input name="password" :label="__('users.password')" type="password" autocomplete="new-password" />
                <x-form.input name="password_confirmation" :label="__('users.confirm_password')" type="password"
                              autocomplete="new-password" />
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('users.save_changes') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('users.cancel') }}</a>
        </div>
    </form>
@endsection
