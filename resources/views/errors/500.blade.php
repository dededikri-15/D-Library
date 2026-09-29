@extends("layouts.app")

@section("title", "500")

@section("content")
    @include("errors._message", ["code" => 500])
@endsection
