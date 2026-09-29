@extends("layouts.app")

@section("title", "503")

@section("content")
    @include("errors._message", ["code" => 503])
@endsection
