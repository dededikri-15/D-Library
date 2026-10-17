@extends('layouts.app')

@section('title', __('profile.title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('profile.title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('profile.description') }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
    @include('partials.profile.identity-card', ['user' => $user])

    @include('partials.profile.edit-form', ['user' => $user])
    </div>
@endsection
