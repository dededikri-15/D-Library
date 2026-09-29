@extends('layouts.app')

@section('title', 'Penulis - ' . config('app.name'))

@section('content')
    @include('partials.master-index', [
        'title' => 'Penulis',
        'routeBase' => 'authors',
        'rows' => $authors,
        'searchAction' => route('authors.index'),
        'subtitleField' => 'biography',
    ])
@endsection
