@extends('layouts.app')

@section('title', __('master.authors').' - '.config('app.name'))

@section('content')
    @include('partials.master-index', [
        'title' => __('master.authors'),
        'routeBase' => 'authors',
        'rows' => $authors,
        'searchAction' => route('authors.index'),
        'subtitleField' => 'biography',
    ])
@endsection
