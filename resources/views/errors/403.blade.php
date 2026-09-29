@extends("layouts.app")

@section("title", "403")

@section("content")
    @include("errors._message", ["code" => 403])
@endsection
