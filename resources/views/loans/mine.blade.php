@extends('layouts.app')

@section('title', __('loans.history_title').' - '.config('app.name'))

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-h1 font-semibold text-primary">{{ __('loans.history_title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('loans.history_description') }}
            </p>
        </div>
        <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">{{ __('loans.search_books') }}</a>
    </div>


    @include('partials.loans.suspension-alert')

    @include('partials.loans.member-summary', ['activeCount' => $activeCount, 'overdueCount' => $overdueCount, 'returnedCount' => $returnedCount])

    @include('partials.loans.member-table', ['loans' => $loans])

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
