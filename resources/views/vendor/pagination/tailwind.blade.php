{{--
    Pagination (Task 14.9).

    Menggantikan `pagination::tailwind` bawaan Laravel. Bedanya bukan cuma
    warna:

    1. Bawaan memakai abu-abu/biru stok yang tidak ada hubungannya dengan palet
       Slate/Indigo proyek ini, dan view itu dipakai di 7 halaman daftar.

    2. Bawaan menyembunyikan nomor halaman di bawah `sm` dan hanya menyisakan
       tombol Sebelumnya/Berikutnya. Di katalog buku dengan puluhan halaman,
       user di HP tidak punya cara lain untuk tahu dia sedang di halaman
       berapa atau totalnya berapa. Jadi nomornya tetap tampil, dipangkas
       supaya muat di 360px.

    Tombol nonaktif memakai `aria-disabled` + `<span>`, bukan `<a>` tanpa
    `href`: supaya tidak bisa di-Tab dan tidak diklik ke URL yang sama.

    CATATAN: jangan sebut nama class utility di dalam komentar Blade. Semua
    file view ikut dipindai Tailwind, jadi utility yang ditulis di komentar
    tetap di-generate dan menambah ukuran CSS tanpa ada yang memakainya.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman">
        <div class="pagination">
            <p class="pagination-summary">
                @if ($paginator->firstItem())
                    Menampilkan <span class="font-medium">{{ $paginator->firstItem() }}</span>–<span
                        class="font-medium">{{ $paginator->lastItem() }}</span> dari
                    <span class="font-medium">{{ $paginator->total() }}</span> data
                @else
                    {{ $paginator->count() }} data
                @endif
            </p>

            <div class="pagination-list">
                @if ($paginator->onFirstPage())
                    <span class="pagination-item pagination-item-disabled" aria-disabled="true">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        <span class="sr-only sm:not-sr-only">Sebelumnya</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                        class="pagination-item">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        <span class="sr-only sm:not-sr-only">Sebelumnya</span>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="pagination-gap" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-item pagination-item-current" aria-current="page">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="pagination-item"
                                    aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-item">
                        <span class="sr-only sm:not-sr-only">Berikutnya</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @else
                    <span class="pagination-item pagination-item-disabled" aria-disabled="true">
                        <span class="sr-only sm:not-sr-only">Berikutnya</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
