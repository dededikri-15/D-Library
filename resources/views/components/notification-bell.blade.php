{{--
    Lonceng notifikasi di topbar (Task 22.x).

    Isi panel dirender oleh server setiap halaman dimuat — tidak ada polling
    dan tidak ada JS untuk memuat daftarnya. Angka pada lonceng ikut basi
    sampai user pindah halaman... kecuali panelnya dibuka: begitu lonceng
    diklik, `initNotificationBell()` di `app.js` langsung menandai SEMUA
    notifikasi sudah dibaca lewat fetch, jadi angkanya hilang di saat yang
    sama tanpa memuat ulang halaman (yang justru akan menutup panel itu).

    Isinya maksimal 8 notifikasi terbaru; riwayat lengkap ada di halaman
    "Notifikasi" (lihat `notifications.index`).
--}}
@php
    $user = auth()->user();
    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
    $latest = $user ? $user->notifications()->latest()->take(8)->get() : collect();
    $badge = $unreadCount > 99 ? '99+' : (string) $unreadCount;
@endphp

<div data-notification-bell
    data-read-all-url="{{ route('notifications.readAll') }}"
    data-empty-text="{{ __('notifications.bell_label').' — '.__('notifications.bell_none') }}"
    class="shrink-0">
<x-dropdown align="right" menu-label="{{ __('notifications.menu_label') }}"
    trigger-class="relative h-10 w-10 justify-center p-0">
    <x-slot:trigger>
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($unreadCount > 0)
            {{--
                Angka kecil di pojok, sengaja tidak pakai `badge` bawaan
                (padding + label lebar) karena ruangnya cuma 1rem. Warnanya
                `brand` (indigo), bukan merah: merah di aplikasi ini khusus
                status terlambat, sementara "belum dibaca" bukan status.
            --}}
            <span data-notification-badge aria-hidden="true"
                class="absolute -top-0.5 -right-0.5 grid min-h-4 min-w-4 place-items-center rounded-full bg-brand px-1 text-[0.625rem] leading-none font-bold text-on-brand">
                {{ $badge }}
            </span>
        @endif

        {{--
            Nama menu + jumlah tidak dibacakan sebagai label tombol biasa
            (aria-label) karena `dropdown-trigger` sudah punya
            `aria-haspopup`/`aria-expanded` — cukup satu teks tersembunyi
            berisi keduanya. Teksnya diganti JS setelah semua ditandai sudah
            dibaca, jadi pembaca layar ikut mendengar "tidak ada yang belum
            dibaca" tanpa perlu memuat ulang halaman.
        --}}
        <span class="sr-only" data-notification-unread-text>
            {{ __('notifications.bell_label') }} —
            {{ $unreadCount > 0
                ? trans_choice(__('notifications.bell_unread'), $unreadCount)
                : __('notifications.bell_none') }}
        </span>
    </x-slot:trigger>

    <x-slot:menu>
        <div class="w-72">
            <p role="presentation" class="dropdown-label">{{ __('notifications.menu_label') }}</p>

            @if ($latest->isEmpty())
                <div class="px-3 py-7 text-center">
                    <svg class="mx-auto h-8 w-8 text-secondary/60" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <p class="mt-2 text-sm text-secondary">{{ __('notifications.empty_short') }}</p>
                </div>
            @else
                <ul role="list" class="max-h-80 overflow-y-auto">
                    @foreach ($latest as $notification)
                        <x-notification-item :notification="$notification" compact />
                    @endforeach
                </ul>
            @endif

            <div role="separator" class="dropdown-separator"></div>

            <a href="{{ route('notifications.index') }}" role="menuitem" class="dropdown-item">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                <span class="truncate">{{ __('notifications.view_all') }}</span>
                @if ($unreadCount > 0)
                    <span data-notification-count
                        class="ml-auto shrink-0 text-label font-semibold text-tertiary">{{ $badge }}</span>
                @endif
            </a>

            {{--
                Tombol ini jalur cadangan tanpa JS (mis. JavaScript diblokir):
                dengan JS aktif, panel yang dibuka sudah menandai semua, jadi
                tombolnya ikut dihapus dari DOM oleh `app.js`.
            --}}
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}" role="none"
                    data-notification-mark-all>
                    @csrf
                    <button type="submit" role="menuitem" class="dropdown-item">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                        </svg>
                        <span class="truncate">{{ __('notifications.mark_all') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </x-slot:menu>
</x-dropdown>
</div>
