@extends('layouts.app')

@section('title', $message->subject . ' - ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between">
        <a href="{{ route('mailbox.index') }}" class="link-accent text-sm font-medium">
            &larr; Kembali ke Mailbox
        </a>
        <form method="POST" action="{{ route('mailbox.destroy', $message->id) }}"
              data-confirm="Hapus email ini?"
              data-confirm-title="Hapus email">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm">Hapus email</button>
        </form>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-hairline px-6 py-4">
            <h1 class="text-xl font-semibold text-primary">{{ $message->subject }}</h1>
            <div class="mt-3 space-y-1 text-sm text-secondary">
                <p><span class="font-medium text-primary">Dari:</span> {{ $message->from_name ?? $message->from_address }} &lt;{{ $message->from_address }}&gt;</p>
                <p><span class="font-medium text-primary">Kepada:</span> {{ $message->to_address }}</p>
                <p><span class="font-medium text-primary">Waktu:</span> {{ \Carbon\Carbon::parse($message->created_at)->format('d M Y, H:i') }}</p>
            </div>
        </div>
        <div class="px-6 py-6">
            <div class="prose max-w-none text-secondary">
                {!! $message->body ?? '<p class="italic text-secondary">Email ini tidak memiliki konten HTML.</p>' !!}
            </div>
        </div>
    </div>
@endsection
