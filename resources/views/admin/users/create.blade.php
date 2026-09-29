@extends('layouts.app')

@section('title', 'Tambah Pengguna - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Tambah Pengguna</h1>
    <p class="mt-1 text-sm text-secondary">Buat akun baru dan tentukan rolenya.</p>

    <form method="POST" action="{{ route('users.store') }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf

        <x-form.input name="name" label="Nama" required :value="$user->name" />
        <x-form.input name="email" label="Email" type="email" required :value="$user->email" autocomplete="email" />
        <x-form.input name="password" label="Kata sandi" type="password" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" label="Ulangi kata sandi" type="password" required
                      autocomplete="new-password" />

        <x-form.select name="role" label="Role" required :allowEmpty="false"
                       :options="collect(App\Models\User::roles())
                            ->mapWithKeys(fn ($role) => [$role => App\Models\User::ROLE_LABELS[$role] ?? $role])
                            ->all()"
                       :value="App\Models\User::ROLE_ANGGOTA" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
