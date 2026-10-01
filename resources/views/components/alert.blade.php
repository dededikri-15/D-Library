@props([
    'variant' => 'success',
    'title' => null,
    'dismissible' => true,
    // Kalau true, toast hilang sendiri setelah beberapa detik (lihat app.js).
    'autoDismiss' => false,
    // Durasi dalam milidetik; null = pakai bawaan `app.js`.
    'duration' => null,
])

@php
    // Opacity latar dinaikkan di dark mode lewat `dark:` karena warna dasar
    // yang terang di atas permukaan gelap akan hilang kalau tetap `/10`.
    $tones = [
        'success' => ['wrap' => 'border-available/40 bg-available/10 dark:bg-available/15', 'icon' => 'text-available'],
        'error' => ['wrap' => 'border-overdue/40 bg-overdue/10 dark:bg-overdue/15', 'icon' => 'text-overdue'],
        'warning' => ['wrap' => 'border-borrowed/40 bg-borrowed/10 dark:bg-borrowed/15', 'icon' => 'text-borrowed'],
        'info' => ['wrap' => 'border-tertiary/40 bg-tertiary/10 dark:bg-tertiary/15', 'icon' => 'text-tertiary'],
    ];
    $tone = $tones[$variant] ?? $tones['info'];

    $icons = [
        'success' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'error' => 'M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z',
        'warning' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        'info' => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
    ];
    $iconPath = $icons[$variant] ?? $icons['info'];
@endphp

{{--
    Alert / toast.

    Satu komponen untuk dua tempat: alert inline di dalam halaman (galat
    validasi) dan toast melayang di pojok (flash message `session('status')`).
    Bentuk dan warna keduanya sama, jadi cukup satu file.

    JS membuat toast dari `<template>` yang merender komponen ini, lalu
    mengisi `data-toast-body`. Markup hanya ada di satu tempat — kalau ditulis
    ulang sebagai string di `app.js`, warna dan ikon akan cepat melenceng dari
    versi Blade.
--}}

<div data-toast
     @if ($autoDismiss) data-auto-dismiss @endif
     @if ($duration) data-toast-duration="{{ $duration }}" @endif
     {{-- Margin purposefully TIDAK default: yang menaruh toast memakai
          `gap` pada wilayahnya, sedangkan alert inline memakai `mb-6`
          sendiri. Dua-duanya butuh jarak yang berbeda. --}}
     {{ $attributes->class(['flex items-start gap-3 rounded-lg border px-4 py-3', $tone['wrap']]) }}
     role="{{ $variant === 'error' ? 'alert' : 'status' }}"
     aria-live="{{ $variant === 'error' ? 'assertive' : 'polite' }}">

    <svg class="{{ $tone['icon'] }} mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24"
         stroke="currentColor" stroke-width="1.7" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
    </svg>

    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="text-sm font-semibold {{ $tone['icon'] }}">{{ $title }}</p>
        @endif

        @if (! $slot->isEmpty())
            <div class="text-sm text-primary">{{ $slot }}</div>
        @endif
    </div>

    @if ($dismissible)
        <button type="button" data-toast-close
                class="shrink-0 cursor-pointer rounded p-0.5 text-secondary transition-colors hover:text-primary"
                aria-label="{{ __('shared.close_notice') }}">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>
