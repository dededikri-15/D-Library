@extends('layouts.app')

@section('title', 'Tambah Penerbit - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Tambah Penerbit</h1>
    <p class="mt-1 text-sm text-secondary">Penerbit ditautkan ke buku lewat form buku.</p>

    <form method="POST" action="{{ route('publishers.store') }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf

        <x-form.input name="name" label="Nama penerbit" required :value="$publisher->name" />
        <x-form.input name="address" label="Alamat" :value="$publisher->address" />
        <x-form.input name="website" label="Situs web" type="url" :value="$publisher->website" />
        <x-form.input name="email" label="Email" type="email" :value="$publisher->email" />
        <x-form.input name="phone" label="Telepon" :value="$publisher->phone" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('publishers.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
