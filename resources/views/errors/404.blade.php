@extends("layouts.app")

@section("title", "404")

@section("content")
    @include("errors._message", ["code" => 404])
@endsection
