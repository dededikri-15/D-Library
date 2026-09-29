@extends("layouts.app")

@section("title", "429")

@section("content")
    @include("errors._message", ["code" => 429])
@endsection
