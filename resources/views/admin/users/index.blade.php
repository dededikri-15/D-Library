@extends('layouts.app')

@section('title', 'Kelola Pengguna - ' . config('app.name'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-h1 font-semibold text-primary">Kelola Pengguna</h1>
            <p class="mt-1 text-sm text-secondary">Tambah akun staff, ubah role, atau hapus pengguna.</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">Tambah pengguna</a>
    </div>

    <form method="GET" class="card mt-6 grid gap-4 p-5 sm:grid-cols-3 lg:grid-cols-4">
        <div>
            <label for="q" class="field-label">Cari nama</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" class="field-input">
        </div>

        <div>
            <label for="role" class="field-label">Filter role</label>
            <select id="role" name="role" class="field-input">
                <option value="">Semua role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>
                        {{ App\Models\User::ROLE_LABELS[$role] ?? $role }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 sm:col-span-3 lg:col-span-4">
            <button type="submit" class="btn btn-primary">Terapkan</button>
            <a href="{{ route('users.index') }}" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    <div class="table-wrap mt-6">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th class="w-40">Role</th>
                    <th class="w-40 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="font-medium text-primary">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="badge ml-2 bg-tertiary/10 text-tertiary">Anda</span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-tertiary/10 text-tertiary">{{ $user->roleLabel() }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-ghost btn-sm">Edit</a>

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                          data-confirm="Hapus pengguna {{ $user->name }}? Tindakan ini tidak bisa dibatalkan."
                                          data-confirm-title="Hapus pengguna">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-0">
                            <x-empty-state class="border-0"
                                           title="Tidak ada pengguna yang cocok"
                                           description="Coba kata kunci lain atau reset filter." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
