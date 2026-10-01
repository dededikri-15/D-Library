@extends('layouts.app')

@section('title', __('master.categories').' - '.config('app.name'))

@section('content')
    @include('partials.master-index', [
        'title' => __('master.categories'),
        'routeBase' => 'categories',
        'rows' => $categories,
        'searchAction' => route('categories.index'),
        'subtitleField' => 'description',
    ])
@endsection
