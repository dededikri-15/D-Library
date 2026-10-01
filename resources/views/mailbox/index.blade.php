@extends('layouts.app')

@section('title', __('mailbox.title').' - '.config('app.name'))

@section('content')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-h1 font-semibold text-primary">{{ __('mailbox.title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('mailbox.description') }}</p>
        </div>
        <a href="{{ route('anggota.dashboard') }}" class="link-accent text-sm font-medium">
            &larr; {{ __('mailbox.back_dashboard') }}
        </a>
    </div>

    @if(session('success'))
        <x-alert variant="success" class="mt-6">{{ session('success') }}</x-alert>
    @endif

    @if($messages->isEmpty())
        <div class="card mt-6 flex flex-col items-center justify-center px-6 py-16 text-center">
            <div class="grid h-16 w-16 place-items-center rounded-full bg-surface-secondary">
                <svg class="h-8 w-8 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 5.625a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            <h2 class="mt-4 text-lg font-semibold text-primary">{{ __('mailbox.empty_title') }}</h2>
            <p class="mt-2 text-sm text-secondary">{{ __('mailbox.empty_description') }}</p>
        </div>
    @else
        <div class="table-wrap mt-6">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('mailbox.from') }}</th>
                        <th>{{ __('mailbox.to') }}</th>
                        <th>{{ __('mailbox.subject') }}</th>
                        <th>{{ __('mailbox.time') }}</th>
                        <th class="text-right">{{ __('mailbox.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($messages as $msg)
                        <tr>
                            <td class="font-medium text-primary">{{ $msg->from_name ?? $msg->from_address }}</td>
                            <td class="text-secondary">{{ $msg->to_address }}</td>
                            <td class="font-medium text-primary">
                                <a href="{{ route('mailbox.show', $msg->id) }}" class="link-accent">
                                    {{ $msg->subject }}
                                </a>
                            </td>
                            <td class="text-secondary">{{ \Carbon\Carbon::parse($msg->created_at)->diffForHumans() }}</td>
                            <td>
                                <div class="flex items-center justify-end">
                                    <form method="POST" action="{{ route('mailbox.destroy', $msg->id) }}"
                                          data-confirm="{{ __('mailbox.delete_confirmation') }}"
                                          data-confirm-title="{{ __('mailbox.delete_title') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('member.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $messages->links() }}</div>
    @endif
@endsection
