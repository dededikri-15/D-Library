@extends('layouts.app')

@section('title', __('categories.public_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('categories.public_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">
        {{ __('categories.public_description') }}
    </p>

    @forelse ($categories as $category)
        <a href="{{ route('books.index', ['category' => $category->slug]) }}"
           class="card mt-4 flex items-center justify-between gap-4 p-5 transition-colors hover:border-tertiary/40">
            <div class="min-w-0">
                <h2 class="font-semibold text-primary">{{ $category->name }}</h2>
                @if ($category->description)
                    <p class="mt-1 line-clamp-2 text-sm text-secondary">{{ $category->description }}</p>
                @endif
            </div>

            <span class="badge shrink-0 bg-tertiary/10 text-tertiary">
                {{ __('categories.book_count', ['count' => $category->books_count]) }}
            </span>
        </a>
    @empty
        <x-empty-state class="mt-6"
                       :title="__('categories.empty_title')"
                       :description="__('categories.empty_description')" />
    @endforelse
@endsection
