@extends('layouts.app')

@section('title', $message->subject . ' - ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between">
        <a href="{{ route('mailbox.index') }}" class="link-accent text-sm font-medium">
            &larr; {{ __('mailbox.back') }}
        </a>
        <form method="POST" action="{{ route('mailbox.destroy', $message->id) }}"
              data-confirm="{{ __('mailbox.delete_confirmation') }}"
              data-confirm-title="{{ __('mailbox.delete_title') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">{{ __('mailbox.delete') }}</button>
        </form>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-hairline px-6 py-4">
            <h1 class="text-xl font-semibold text-primary">{{ $message->subject }}</h1>
            <div class="mt-3 space-y-1 text-sm text-secondary">
                <p><span class="font-medium text-primary">{{ __('mailbox.from') }}:</span> {{ $message->from_name ?? $message->from_address }} &lt;{{ $message->from_address }}&gt;</p>
                <p><span class="font-medium text-primary">{{ __('mailbox.to') }}:</span> {{ $message->to_address }}</p>
                <p><span class="font-medium text-primary">{{ __('mailbox.time') }}:</span> {{ \Carbon\Carbon::parse($message->created_at)->format('d M Y, H:i') }}</p>
            </div>
        </div>
        <div class="px-6 py-6">
            <div class="max-w-none whitespace-pre-wrap break-words text-secondary">
                {{ $message->body ?? __('mailbox.no_content') }}
            </div>
        </div>
    </div>
@endsection
