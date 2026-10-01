<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full"
    data-ui-action-failed="{{ __('ui.action_failed') }}"
    data-ui-saved="{{ __('ui.saved') }}"
    data-ui-session-expired="{{ __('ui.session_expired') }}"
    data-ui-login-required="{{ __('ui.login_required') }}"
    data-ui-response-unreadable="{{ __('ui.response_unreadable') }}"
    data-ui-network-error="{{ __('ui.network_error') }}"
    data-ui-network-fallback="{{ __('ui.network_fallback') }}"
    data-ui-search-throttled="{{ __('ui.search_throttled') }}"
    data-ui-search-failed="{{ __('ui.search_failed') }}"
    data-ui-saving="{{ __('ui.saving') }}"
    data-ui-save-failed="{{ __('ui.save_failed') }}"
    data-ui-incomplete-response="{{ __('ui.incomplete_response') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    {{--
        Script tema harus jalan SEBELUM CSS dirender, kalau tidak halaman akan
        berkedip putih saat user membuka situs dalam mode gelap. Karena itu
        ditulis inline di sini, bukan di app.js yang dimuat setelah CSS.
        `try/catch` menjaga agar localStorage yang diblokir (mode privat,
        cookie diblokir) tidak membuat halaman gagal total.
    --}}
    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            try {
                var theme = localStorage.getItem('perpustakaan-theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (theme !== 'light' && theme !== 'dark') {
                    theme = prefersDark ? 'dark' : 'light';
                }

                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }

                document.documentElement.dataset.theme = theme;
            } catch (e) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-neutral text-primary antialiased">
    <a href="#konten"
       class="sr-only focus:not-sr-only focus:absolute focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:text-on-brand">
        Lewati ke konten utama
    </a>

    <x-navbar />

    <div class="app-content flex min-h-[calc(100vh-4rem)] flex-col transition-[margin] duration-200 lg:ml-64">
        <main id="konten" class="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
            {{-- Galat validasi sengaja tetap inline, bukan toast (Task 14.5):
                 isinya memandu pengisian form dan tidak boleh hilang sendiri. --}}
            @if ($errors->any())
                <x-alert variant="error" class="mb-6" :title="__('ui.validation_heading')">
                    <ul class="mt-1 list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            @yield('content')
        </main>

        <x-footer />
    </div>

    <button type="button" data-back-to-top hidden
        class="fixed right-5 bottom-5 z-40 grid h-11 w-11 place-items-center rounded-full border border-hairline-strong bg-surface/95 text-secondary shadow-card backdrop-blur transition-colors hover:border-tertiary/40 hover:bg-surface hover:text-tertiary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tertiary motion-reduce:transition-none"
        aria-label="{{ __('ui.back_to_top') }}" title="{{ __('ui.back_to_top') }}">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"
            aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 15 6-6 6 6" />
        </svg>
    </button>

    {{--
        Toast (Task 14.5). Flash message `session('status')` dirender di dalam
        wilayah ini supaya muncul melayang di pojok, bukan mendorong isi
        halaman. Alert galat validasi tetap inline di <main> di atas.

        `session('error')` dipakai untuk aksi yang ditolak karena datanya masih
        dipakai (mis. hapus kategori yang masih punya buku). Varian merahnya
        penting: pesan "kategori tidak bisa dihapus" yang berwarna hijau akan
        dibaca user sebagai "berhasil dihapus", padahal barisnya masih ada di
        daftar.

        Tidak dibungkus `@auth` karena tamu pun butuh notifikasi: pendaftaran
        anggota dan hasil login sama-sama mem-flash `status`.
    --}}
    <x-toast-region>
        @if (session('status'))
            <x-alert variant="success" class="toast" auto-dismiss>{{ session('status') }}</x-alert>
        @endif

        @if (session('error'))
            <x-alert variant="error" class="toast">{{ session('error') }}</x-alert>
        @endif
    </x-toast-region>

    @auth
        {{--
            Dialog konfirmasi (Task 14.4). Satu dialog dipakai bersama oleh
            semua form yang menulis `data-confirm` — tidak ada dialog per tabel
            atau per baris, jadi halaman yang panjang tetap ringan.

            `autofocus` sengaja ditaruh di tombol Batal: dialog ini hampir
            selalu menjalankan sesuatu yang tidak bisa dibatalkan, jadi fokus
            default harus berada di jalan keluar.
        --}}
        <x-modal id="konfirmasi-tindakan" :title="__('ui.confirm_heading')" size="sm" role="alertdialog">
            <p data-confirm-message class="text-sm leading-relaxed text-secondary"></p>

            <x-slot:footer>
                <button type="button" data-modal-close autofocus class="btn btn-secondary">{{ __('ui.confirm_cancel') }}</button>
                <button type="button" data-confirm-accept class="btn btn-danger">{{ __('ui.confirm_accept') }}</button>
            </x-slot:footer>
        </x-modal>
    @endauth
</body>
</html>
