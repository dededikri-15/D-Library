@extends('layouts.app')

@section('title', __('master.publishers').' - '.config('app.name'))

@section('content')
    @include('partials.master-index', [
        'title' => __('master.publishers'),
        'routeBase' => 'publishers',
        'rows' => $publishers,
        'searchAction' => route('publishers.index'),
        'subtitleField' => 'address',
    ])
@endsection
