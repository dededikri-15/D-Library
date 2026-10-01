@extends('layouts.app')

@section('title', __('master.edit').' '.ucfirst(__('master.publisher')).' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('master.edit') }} {{ ucfirst(__('master.publisher')) }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $publisher->name }}</p>

    <form method="POST" action="{{ route('publishers.update', $publisher) }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" :label="__('master.publisher_name')" required :value="$publisher->name" />
        <x-form.input name="address" :label="__('master.address')" :value="$publisher->address" />
        <x-form.input name="website" :label="__('master.website')" type="url" :value="$publisher->website" />
        <x-form.input name="email" :label="__('master.email')" type="email" :value="$publisher->email" />
        <x-form.input name="phone" :label="__('master.phone')" :value="$publisher->phone" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('master.save_changes') }}</button>
            <a href="{{ route('publishers.index') }}" class="btn btn-secondary">{{ __('master.cancel') }}</a>
        </div>
    </form>

    @include('partials.master-danger-delete', [
        'label' => __('master.publisher'),
        'name' => $publisher->name,
        'action' => route('publishers.destroy', $publisher),
        'booksCount' => $publisher->books_count,
    ])
@endsection
