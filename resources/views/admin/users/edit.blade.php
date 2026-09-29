@extends('layouts.app')

@section('title', 'Edit Pengguna - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Edit Pengguna</h1>
    <p class="mt-1 text-sm text-secondary">{{ $user->email }}</p>

    <form method="POST" action="{{ route('users.update', $user) }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" label="Nama" required :value="$user->name" />
        <x-form.input name="email" label="Email" type="email" required :value="$user->email" autocomplete="email" />

        <x-form.select name="role" label="Role" required :allowEmpty="false"
                       :options="collect(App\Models\User::roles())
                            ->mapWithKeys(fn ($role) => [$role => App\Models\User::ROLE_LABELS[$role] ?? $role])
                            ->all()"
                       :value="$user->role" />

        <div class="rounded-lg border border-secondary/20 p-4">
            <p class="text-sm font-medium text-primary">Ganti kata sandi (opsional)</p>
            <p class="mt-1 text-xs text-secondary">Kosongkan kedua kolom bila kata sandi tidak ingin diubah.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-form.input name="password" label="Kata sandi baru" type="password" autocomplete="new-password" />
                <x-form.input name="password_confirmation" label="Ulangi kata sandi" type="password"
                              autocomplete="new-password" />
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
