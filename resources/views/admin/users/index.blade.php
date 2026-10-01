@extends('layouts.app')

@section('title', __('users.index_title').' - '.config('app.name'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-h1 font-semibold text-primary">{{ __('users.index_title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('users.index_description') }}</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('users.add_user') }}</a>
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

    <div class="table-wrap mt-6">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('users.name') }}</th>
                    <th>{{ __('users.email') }}</th>
                    <th class="w-40">{{ __('users.role') }}</th>
                    <th class="w-40 text-right">{{ __('users.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="font-medium text-primary">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="badge ml-2 bg-tertiary/10 text-tertiary">{{ __('users.you') }}</span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $user->email }}</td>
                        <td>
                            <span class="badge bg-tertiary/10 text-tertiary">{{ $user->roleLabel() }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-ghost btn-sm">{{ __('users.edit') }}</a>

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                          data-confirm="{{ __('users.delete_confirmation', ['name' => $user->name]) }}"
                                          data-confirm-title="{{ __('users.delete_title') }}">
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
                        <td colspan="4" class="p-0">
                            <x-empty-state class="border-0"
                                           :title="__('users.no_match')"
                                           :description="__('users.try_search_again')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
