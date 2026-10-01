@extends('layouts.app')

@section('title', __('master.edit').' '.ucfirst(__('master.category')).' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('master.edit') }} {{ ucfirst(__('master.category')) }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $category->name }}</p>

    <form method="POST" action="{{ route('categories.update', $category) }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" :label="__('master.category_name')" required :value="$category->name" />
        <x-form.input name="slug" :label="__('master.slug')" :value="$category->slug" />
        <x-form.textarea name="description" :label="__('master.description')" rows="3" :value="$category->description" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('master.save_changes') }}</button>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">{{ __('master.cancel') }}</a>
        </div>
    </form>

    @include('partials.master-danger-delete', [
        'label' => __('master.category'),
        'name' => $category->name,
        'action' => route('categories.destroy', $category),
        'booksCount' => $category->books_count,
    ])
@endsection
