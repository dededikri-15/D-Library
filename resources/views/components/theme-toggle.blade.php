{{--
    Tombol toggle dark / light mode.

    Kedua ikon selalu ada di DOM dan hanya visibility-nya yang dikendalikan
    Tailwind lewat `dark:hidden` / `hidden dark:block`. Alasannya: kalau ikon
    ditukar lewat JavaScript, tombol akan kosong sesaat sebelum JS dimuat, dan
    rusak total untuk user yang mematikan JavaScript.

    Ikon menunjukkan "tombol ini akan mengubah ke mode apa": saat mode gelap
    aktif, yang ditampilkan adalah matahari.
--}}

<button type="button"
        data-theme-toggle
        aria-label="Aktifkan mode gelap"
        title="Aktifkan mode gelap"
        class="btn btn-ghost btn-sm group px-2.5">
    {{-- Matahari: tampil saat mode gelap aktif (klik untuk kembali terang) --}}
    <svg class="hidden h-5 w-5 text-tertiary transition-transform duration-300 group-hover:rotate-45 motion-reduce:transform-none dark:block"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <circle cx="12" cy="12" r="4"/>
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 2.5v2m0 15v2m9.5-9.5h-2m-15 0h-2m16.19-6.69-1.42 1.42M6.73 17.27l-1.42 1.42m12.36 0-1.42 1.42m9.52-3.19-1.42-1.42M6.73 6.73 5.31 5.31"/>
    </svg>

    {{-- Bulan: tampil saat mode terang aktif --}}
    <svg class="h-5 w-5 text-tertiary transition-transform duration-300 group-hover:-rotate-12 motion-reduce:transform-none dark:hidden"
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M21.75 15.6A9.72 9.72 0 0 1 8.4 2.25a9.72 9.72 0 1 0 13.35 13.35Z"/>
    </svg>
</button>
