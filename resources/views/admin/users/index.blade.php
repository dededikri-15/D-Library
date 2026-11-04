@extends('layouts.app')

@section('title', __('users.index_title').' - '.config('app.name'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-h1 font-semibold text-primary">{{ __('users.index_title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('users.index_description') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('register.librarian') }}" class="btn btn-secondary">{{ __('users.register_librarian') }}</a>
            <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('users.add_user') }}</a>
        </div>
    </div>

    <form method="GET" class="card mt-6 grid gap-4 p-5 sm:grid-cols-3 lg:grid-cols-4">
        <div>
            <label for="q" class="field-label">{{ __('users.search_name') }}</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" class="field-input">
        </div>

        <div>
            <label for="role" class="field-label">{{ __('users.filter_role') }}</label>
            <select id="role" name="role" class="field-input">
                <option value="">{{ __('users.all_roles') }}</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>
                        {{ __('roles.'.$role) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 sm:col-span-3 lg:col-span-4">
            <button type="submit" class="btn btn-primary">{{ __('users.apply') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-ghost">{{ __('users.reset') }}</a>
        </div>
    </form>

    <div class="mt-6 space-y-6">
        <section class="card p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-primary">Daftar Anggota</h2>
                <span class="badge bg-success/10 text-success">{{ $members->count() }} akun</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('users.name') }}</th>
                            <th>{{ __('users.email') }}</th>
                            <th>{{ __('users.gender') }}</th>
                            <th>{{ __('users.date_of_birth') }}</th>
                            <th class="w-40 text-right">{{ __('users.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $user)
                            <tr>
                                <td class="font-medium text-primary">
                                    <span class="flex items-center gap-2.5">
                                        <x-avatar :user="$user" size="xs" alt="" />
                                        <span class="min-w-0">
                                            <span class="block truncate">{{ $user->name }}</span>
                                            @if ($user->id === auth()->id())
                                                <span class="badge mt-1 bg-tertiary/10 text-tertiary">{{ __('users.you') }}</span>
                                            @endif
                                        </span>
                                    </span>
                                </td>
                                <td class="text-secondary">{{ $user->email }}</td>
                                <td class="text-secondary">{{ $user->genderLabel() ?? __('users.gender_not_set') }}</td>
                                <td class="text-secondary">{{ $user->date_of_birth?->translatedFormat('d M Y') ?? __('users.gender_not_set') }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm">{{ __('users.edit') }}</a>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="{{ __('users.delete_confirmation', ['name' => $user->name]) }}" data-confirm-title="{{ __('users.delete_title') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">{{ __('users.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-0">
                                    <x-empty-state class="border-0" title="Belum ada anggota" description="Anggota baru akan muncul di sini setelah pendaftaran." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card p-5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-primary">Daftar Pustakawan</h2>
                <span class="badge bg-warning/10 text-warning">{{ $librarians->count() }} akun</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('users.name') }}</th>
                            <th>{{ __('users.email') }}</th>
                            <th>{{ __('users.gender') }}</th>
                            <th>{{ __('users.date_of_birth') }}</th>
                            <th class="w-40 text-right">{{ __('users.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($librarians as $user)
                            <tr>
                                <td class="font-medium text-primary">
                                    <span class="flex items-center gap-2.5">
                                        <x-avatar :user="$user" size="xs" alt="" />
                                        <span class="min-w-0">
                                            <span class="block truncate">{{ $user->name }}</span>
                                            @if ($user->id === auth()->id())
                                                <span class="badge mt-1 bg-tertiary/10 text-tertiary">{{ __('users.you') }}</span>
                                            @endif
                                        </span>
                                    </span>
                                </td>
                                <td class="text-secondary">{{ $user->email }}</td>
                                <td class="text-secondary">{{ $user->genderLabel() ?? __('users.gender_not_set') }}</td>
                                <td class="text-secondary">{{ $user->date_of_birth?->translatedFormat('d M Y') ?? __('users.gender_not_set') }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm">{{ __('users.edit') }}</a>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm="{{ __('users.delete_confirmation', ['name' => $user->name]) }}" data-confirm-title="{{ __('users.delete_title') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">{{ __('users.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-0">
                                    <x-empty-state class="border-0" title="Belum ada pustakawan" description="Pustakawan baru dapat ditambahkan dari form pendaftaran user di sini." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
