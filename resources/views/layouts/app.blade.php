<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
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
    <script>
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
                <x-alert variant="error" class="mb-6" title="Periksa kembali isian Anda">
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

    {{--
        Toast (Task 14.5). Flash message `session('status')` dirender di dalam
        wilayah ini supaya muncul melayang di pojok, bukan mendorong isi
        halaman. Alert galat validasi tetap inline di <main> di atas.

        Tidak dibungkus `@auth` karena tamu pun butuh notifikasi: pendaftaran
        anggota dan hasil login sama-sama mem-flash `status`.
    --}}
    <x-toast-region>
        @if (session('status'))
            <x-alert variant="success" class="toast" auto-dismiss>{{ session('status') }}</x-alert>
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
        <x-modal id="konfirmasi-tindakan" title="Konfirmasi tindakan" size="sm" role="alertdialog">
            <p data-confirm-message class="text-sm leading-relaxed text-secondary"></p>

            <x-slot:footer>
                <button type="button" data-modal-close autofocus class="btn btn-secondary">Batal</button>
                <button type="button" data-confirm-accept class="btn btn-danger">Ya, lanjutkan</button>
            </x-slot:footer>
        </x-modal>
    @endauth
</body>
</html>
