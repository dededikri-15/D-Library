@extends('layouts.app')

@section('title', __('reader.title', ['title' => $book->title]).' - '.config('app.name'))

@section('content')
    {{--
        Halaman ini TIDAK PERNAH memuat PDF-nya secara langsung. Berkas
        dit-stream oleh route `books.file`, yang juga memeriksa hak akses, jadi
        tidak ada URL PDF publik yang bisa ditebak atau dibagikan.

        Batasan yang perlu diketahui: viewer bawaan browser tidak memberi
        akses ke nomor halaman yang sedang dilihat. Halaman ini karena itu
        tidak boleh menebak posisi scroll. User sendiri yang menekan "Simpan
        posisi", dan hanya baris itulah yang memakai input di bawah.
        Mengklaim pelacakan otomatis tanpa kemampuan teknisnya akan membuat
        fitur "lanjut membaca" membuka halaman yang salah.
    --}}

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('books.show', $book) }}" class="text-sm text-secondary transition-colors hover:text-tertiary">
                &larr; {{ __('reader.back_to_details') }}
            </a>
            <h1 class="mt-1 text-h1 font-semibold text-primary">{{ $book->title }}</h1>
            <p class="mt-1 text-sm text-secondary">
                {{ __('reader.by') }} {{ $book->author?->name ?? __('reader.unknown_author') }}
                @if ($book->category)
                    &middot; {{ $book->category->name }}
                @endif
            </p>
        </div>

        @if ($savedPage > 0)
            <p class="pill shrink-0 border-available/40 bg-available/10 text-available dark:bg-available/15">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                {{ __('reader.saved_page', ['page' => $savedPage]) }}
            </p>
        @endif
    </div>

    {{-- Toolbar: pindah halaman + simpan posisi. --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <form method="GET" action="{{ route('books.read', $book) }}" class="card flex flex-wrap items-end gap-3 p-4"
              data-reader-jump>
            <div class="w-24">
                <label for="page" class="field-label">{{ __('reader.page') }}</label>
                {{-- min 1: clamp juga di server, tapi validasi di sisi user
                     lebih cepat umpan baliknya. --}}
                <input id="page" name="page" type="number" inputmode="numeric" min="1"
                       @if ($totalPages) max="{{ $totalPages }}" @endif
                       value="{{ $page }}"
                       class="field-input tabular-nums"
                       data-reader-page>
            </div>

            @if ($totalPages)
                <p class="pb-2.5 text-sm text-secondary">{{ __('reader.of_pages', ['count' => number_format($totalPages, 0, ',', '.')]) }}</p>
            @endif

            <div class="ml-auto flex gap-2">
                <button type="submit" class="btn btn-secondary">{{ __('reader.open_page') }}</button>
            </div>
        </form>

        <form method="POST" action="{{ route('reading-histories.store') }}" class="card flex flex-wrap items-end gap-3 p-4"
              data-submit-once>
            @csrf
            <input type="hidden" name="book_id" value="{{ $book->id }}">
            <input type="hidden" name="last_page" value="{{ $page }}" data-reader-save>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-primary">{{ __('reader.save_position') }}</p>
                <p class="mt-0.5 text-label text-secondary">
                    {{ __('reader.save_page_hint', ['page' => $page]) }}
                </p>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('reader.save_position_button') }}</button>
        </form>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-hairline bg-surface shadow-card">
        {{--
            Kontrol zoom (Task 11.9). Tombolnya sengaja dibuat MUNCUL OLEH JS:
            tanpa JavaScript mereka tidak bisa berbuat apa-apa, dan tombol mati
            di layar hanya membingungkan. Selain itu viewer bawaan browser
            tetap punya zoom sendiri, jadi tanpa JS pembaca tidak kehilangan
            apa pun.

            Caranya bukan `transform: scale()` — transformasi tidak ikut
            memengaruhi layout, sehingga scrollbar tidak akan mengikuti isi
            yang membesar. Ukuran kotak `<object>`-nya yang diperbesar lewat
            variabel CSS, dan viewer PDF di dalamnya menggambar ulang mengikuti
            lebar kotak itu (fit-to-width) — itulah arti "zoom".
        --}}
        <div class="hidden items-center justify-between gap-3 border-b border-hairline px-4 py-2.5"
             data-reader-zoom-controls>
            <p class="text-label font-medium tracking-wide text-secondary uppercase">
                {{ __('reader.zoom_group') }}
            </p>

            <div class="flex items-center gap-1.5">
                <button type="button" class="btn btn-secondary btn-sm px-2.5"
                        data-reader-zoom-out aria-label="{{ __('reader.zoom_out') }}">
                    &minus;
                </button>

                <span class="w-14 text-center text-sm font-semibold tabular-nums text-primary"
                      data-reader-zoom-level aria-live="polite">100%</span>

                <button type="button" class="btn btn-secondary btn-sm px-2.5"
                        data-reader-zoom-in aria-label="{{ __('reader.zoom_in') }}">
                    +
                </button>

                <button type="button" class="btn btn-ghost btn-sm" data-reader-zoom-reset>
                    {{ __('reader.zoom_reset') }}
                </button>

                <span class="mx-1 h-5 w-px bg-hairline" aria-hidden="true"></span>

                {{--
                    "Tutup PDF" hanya menyembunyikan tampilannya — dokumen tidak
                    dibuang, jadi saat dibuka lagi posisi terakhir yang diketahui
                    (input halaman / fragment `#page=`) langsung dipasang kembali
                    oleh `initReaderClose()`.
                --}}
                <button type="button" class="btn btn-ghost btn-sm" data-reader-close>
                    {{ __('reader.close_pdf') }}
                </button>
            </div>
        </div>

        {{--
            Elemen ini diganti barunya oleh app.js saat user menekan "Buka
            halaman" (lihat `reloadReaderTo()`). Menimpa atribut `data` yang
            hanya berbeda pada fragmen `#page=` TIDAK menggerakkan viewer PDF
            bawaan browser — perubahan begitu dianggap sebagai perubahan URL di
            dalam dokumen yang sama — jadi tombolnya jadi terasa mati. Elemen
            baru selalu dimuat dari nol dan menghormati `#page=`; biaya
            unduhannya dijaga murah oleh cache + ETag dari `books.file`.

            Tanpa JavaScript, perpindahan halaman tetap jalan lewat form GET di
            atas (halaman dimuat ulang penuh dengan `?page=N`).

            Fallback di dalam <object> dipakai browser yang tidak punya plugin
            PDF bawaan. Catatan penting: isi CSP halaman ini harus memuat
            `object-src 'self'` — `object-src 'none'` memblokir sematan ini dan
            fallback akan tampil terus walau file-nya sehat (dijaga test di
            SecurityTest).
        --}}
        <div class="reader-viewport" data-reader-viewport>
            <object data="{{ route('books.file', $book) }}#page={{ $page }}" type="application/pdf"
                    data-reader-target
                    data-reader-file-url="{{ route('books.file', $book) }}">
                <div class="flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
                    <p class="text-sm text-secondary">
                        {{ __('reader.pdf_not_supported') }}
                    </p>
                    <a href="{{ route('books.file', $book) }}#page={{ $page }}" class="btn btn-primary">
                        {{ __('reader.open_pdf') }}
                    </a>
                </div>
            </object>
        </div>

        {{--
            Panel "sudah ditutup". Tersembunyi sampai JS menutup PDF; berisi
            nomor halaman terakhir yang diketahui dan tombol membukanya lagi.
            Tanpa JS panel ini tidak pernah muncul dan tombol "Tutup PDF" juga
            tidak ada, jadi tidak ada tombol mati di layar.
        --}}
        <div class="hidden flex-col items-center justify-center gap-2 p-10 text-center" data-reader-closed>
            <p class="text-sm font-medium text-primary">{{ __('reader.pdf_closed') }}</p>
            <p class="text-label text-secondary" data-reader-closed-page
               data-page-template="{{ __('reader.pdf_closed_page', ['page' => ':page']) }}">
                {{ __('reader.pdf_closed_page', ['page' => $page]) }}
            </p>
            <button type="button" class="btn btn-primary btn-sm mt-2" data-reader-open>
                {{ __('reader.open_pdf_again') }}
            </button>
        </div>
    </div>

    <p class="mt-3 text-xs text-secondary">
        @if (auth()->user()?->isStaff())
            {{ __('reader.staff_access') }}
        @else
            {{ __('reader.member_access') }}
        @endif
        @if ($book->pages > 0)
            &middot; {{ __('reader.total_pages', ['count' => number_format($book->pages, 0, ',', '.')]) }}
        @endif
    </p>
@endsection
