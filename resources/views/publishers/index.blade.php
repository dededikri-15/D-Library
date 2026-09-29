@extends('layouts.app')

@section('title', 'Penerbit - ' . config('app.name'))

@section('content')
    @include('partials.master-index', [
        'title' => 'Penerbit',
        'routeBase' => 'publishers',
        'rows' => $publishers,
        'searchAction' => route('publishers.index'),
        'subtitleField' => 'address',
    ])
@endsection
