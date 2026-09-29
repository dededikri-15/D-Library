@extends('layouts.app')

@section('title', 'Baca: ' . $book->title . ' - ' . config('app.name'))

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
                &larr; Kembali ke detail buku
            </a>
            <h1 class="mt-1 text-h1 font-semibold text-primary">{{ $book->title }}</h1>
            <p class="mt-1 text-sm text-secondary">
                oleh {{ $book->author?->name ?? 'Penulis tidak diketahui' }}
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
                Tersimpan: halaman {{ $savedPage }}
            </p>
        @endif
    </div>

    {{-- Toolbar: pindah halaman + simpan posisi. --}}
    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <form method="GET" action="{{ route('books.read', $book) }}" class="card flex flex-wrap items-end gap-3 p-4"
              data-reader-jump>
            <div class="w-24">
                <label for="page" class="field-label">Halaman</label>
                {{-- min 1: clamp juga di server, tapi validasi di sisi user
                     lebih cepat umpan baliknya. --}}
                <input id="page" name="page" type="number" inputmode="numeric" min="1"
                       @if ($totalPages) max="{{ $totalPages }}" @endif
                       value="{{ $page }}"
                       class="field-input tabular-nums"
                       data-reader-page>
            </div>

            @if ($totalPages)
                <p class="pb-2.5 text-sm text-secondary">dari {{ number_format($totalPages, 0, ',', '.') }} halaman</p>
            @endif

            <div class="ml-auto flex gap-2">
                <button type="submit" class="btn btn-secondary">Buka halaman</button>
            </div>
        </form>

        <form method="POST" action="{{ route('reading-histories.store') }}" class="card flex flex-wrap items-end gap-3 p-4"
              data-submit-once>
            @csrf
            <input type="hidden" name="book_id" value="{{ $book->id }}">
            <input type="hidden" name="last_page" value="{{ $page }}" data-reader-save>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-primary">Simpan posisi baca</p>
                <p class="mt-0.5 text-label text-secondary">
                    Simpan halaman {{ $page }} supaya bisa dilanjutkan nanti.
                </p>
            </div>

            <button type="submit" class="btn btn-primary">Simpan posisi</button>
        </form>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-hairline bg-surface shadow-card">
        {{--
            `data-reader-target` ditimpa oleh app.js: ia hanya mengubah
            bagian setelah tanda pagar, jadi PDF tidak diunduh ulang setiap kali
            pindah halaman. Tanpa JavaScript, perpindahan halaman tetap jalan
            lewat form GET di atas (halaman dimuat ulang penuh).

            Fallback di dalam <object> dipakai browser yang tidak punya plugin
            PDF bawaan.
        --}}
        <object data="{{ route('books.file', $book) }}#page={{ $page }}" type="application/pdf"
                class="h-[75vh] w-full"
                data-reader-target
                data-reader-file-url="{{ route('books.file', $book) }}">
            <div class="flex h-full flex-col items-center justify-center gap-3 p-8 text-center">
                <p class="text-sm text-secondary">
                    Browser ini tidak menampilkan PDF secara langsung.
                </p>
                <a href="{{ route('books.file', $book) }}#page={{ $page }}" class="btn btn-primary">
                    Buka file PDF
                </a>
            </div>
        </object>
    </div>

    <p class="mt-3 text-xs text-secondary">
        @if (auth()->user()?->isStaff())
            Staff dapat membuka semua berkas buku untuk keperluan operasional.
        @else
            Dokumen ini hanya dapat diakses selama kamu sedang meminjam buku ini.
        @endif
        @if ($book->pages > 0)
            &middot; Total {{ number_format($book->pages, 0, ',', '.') }} halaman.
        @endif
    </p>
@endsection
