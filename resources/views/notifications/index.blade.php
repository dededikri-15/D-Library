@extends('layouts.app')

@section('title', __('notifications.title').' - '.config('app.name'))

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-h1 font-semibold text-primary">{{ __('notifications.title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('notifications.description') }}</p>
            @if ($unreadCount > 0)
                <p class="mt-2 text-label font-semibold tracking-wide text-tertiary uppercase">
                    {{ trans_choice(__('notifications.unread_count'), $unreadCount) }}
                </p>
            @endif
        </div>

        <div class="flex shrink-0 items-center gap-2">
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">
                        {{ __('notifications.mark_all') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if ($notifications->isEmpty())
        <x-empty-state class="mt-6"
            :title="__('notifications.empty_title')"
            :description="__('notifications.empty_description')" />
    @else
        {{--
            Kartu biasa, bukan tabel: tiap notifikasi adalah dua baris teks
            yang panjangnya tidak terduga, dan kolom "status"nya cuma satu
            titik belum-dibaca — tabel akan menyisakan kolom kosong lebar.
        --}}
        <div class="card mt-6 p-0">
            <ul role="list" class="divide-y divide-hairline">
                @foreach ($notifications as $notification)
                    <x-notification-item :notification="$notification" />
                @endforeach
            </ul>
        </div>

        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
