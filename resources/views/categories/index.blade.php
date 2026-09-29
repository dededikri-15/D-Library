@extends('layouts.app')

@section('title', 'Kategori - ' . config('app.name'))

@section('content')
    @include('partials.master-index', [
        'title' => 'Kategori',
        'routeBase' => 'categories',
        'rows' => $categories,
        'searchAction' => route('categories.index'),
        'subtitleField' => 'description',
    ])
@endsection
