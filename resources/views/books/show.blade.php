@extends('layouts.app')

@section('title', $book->title . ' - ' . config('app.name'))

@section('content')
    <nav aria-label="Remah roti" class="text-sm">
        <a href="{{ route('books.index') }}" class="inline-flex items-center gap-1.5 text-secondary transition-colors hover:text-tertiary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            {{ __('book.back_to_catalog') }}
        </a>
    </nav>

    {{--
        Layout 2 kolom. Kolom kiri khusus cover — rasio 3/4 membuat kolomnya
        terlalu lebar kalau ikut grid 12 kolom. Kolom kanan jadi seluruh isi.
    --}}
    <div class="mt-6 grid gap-8 lg:grid-cols-12 lg:gap-10">

        {{-- Cover --}}
        <div class="lg:col-span-4 xl:col-span-3">
            <div class="lg:sticky lg:top-28">
                <x-book-cover :book="$book" size="lg"
                              class="shadow-lift ring-1 ring-hairline" />

                @if ($book->hasFile())
                    <p class="mt-3 flex items-center justify-center gap-1.5 text-label text-secondary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.63c0-1.14-.46-2.23-1.28-3.03l-2.4-2.4a4.28 4.28 0 0 0-3.04-1.26H7.5m10.5 9.04v2.63c0 1.14-.46 2.23-1.28 3.03l-2.4 2.4a4.28 4.28 0 0 1-3.04 1.26H7.5m10.5-9.04H4.5a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5h3.09a1.5 1.5 0 0 0 1.06-.44l1.69-1.7a1.5 1.5 0 0 1 1.06-.44h3.6a1.5 1.5 0 0 1 1.5 1.5v3.09a1.5 1.5 0 0 0 .44 1.06l1.7 1.69a1.5 1.5 0 0 0 1.06.44h1.5a1.5 1.5 0 0 1 1.5 1.5Z"/>
                        </svg>
                        {{ __('book.digital_available') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Informasi --}}
        <div class="lg:col-span-8 xl:col-span-9">
            <div class="flex flex-wrap items-center gap-2">
                @if ($book->category)
                    <a href="{{ route('books.index', ['category' => $book->category->slug]) }}"
                       class="badge border border-tertiary/25 bg-tertiary/10 text-tertiary transition-colors
                              hover:border-tertiary/50 hover:bg-tertiary/15 dark:bg-tertiary/15">
                        {{ $book->category->name }}
                    </a>
                @endif
                <x-status-badge :status="$book->status" />
            </div>

            <h1 class="mt-4 text-h1 font-bold tracking-tight text-balance text-primary">{{ $book->title }}</h1>

            <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-body text-secondary">
                <span>{{ __('book.by') }}</span>
                @if ($book->author)
                    <span class="font-semibold text-primary">{{ $book->author->name }}</span>
                @else
                    <span class="text-secondary">{{ __('book.unknown_author') }}</span>
                @endif
                @if ($book->publication_year)
                    <span aria-hidden="true">&middot;</span>
                    <span class="tabular-nums">{{ $book->publication_year }}</span>
                @endif
            </p>

            {{-- Metadata --}}
            <dl class="card mt-7 divide-y divide-hairline overflow-hidden">
                @foreach ([
                    __('book.publisher') => $book->publisher?->name ?? '-',
                    __('book.isbn') => $book->isbn,
                    __('book.pages') => $book->pages ?? '-',
                    __('book.copies_label') => __('book.copies_available', ['available' => $book->available_copies_count, 'total' => $book->copies_count]),
                    __('book.readers') => number_format($readerCount, 0, ',', '.'),
                    __('book.status') => match ($book->status) {
                        App\Models\Book::STATUS_AVAILABLE => __('book.available_for_loan'),
                        App\Models\Book::STATUS_BORROWED => __('book.currently_borrowed'),
                        default => __('book.inactive'),
                    },
                ] as $label => $value)
                    <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                        <dt class="text-sm text-secondary">{{ $label }}</dt>
                        <dd @class([
                            'text-sm font-medium text-primary',
                            // ISBN selalu angka, jadi font monospace memudahkan
                            // dikelompokkan per 4 digit saat diketik ulang.
                            'font-mono tabular-nums' => $label === 'ISBN',
                            'tabular-nums' => $label === 'Jumlah pembaca',
                        ])>{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- Sinopsis --}}
            <section class="mt-8">
                <h2 class="section-title">{{ __('book.synopsis') }}</h2>
                @if ($book->description)
                    {{--
                        `whitespace-pre-line` dipakai karena sinopsis ditulis
                        dengan paragraf baru di textarea, dan Blade meng-escape
                        HTML. Tanpa itu semua baris baru hilang.
                    --}}
                    <p class="mt-3 max-w-none text-body leading-relaxed whitespace-pre-line text-secondary">
                        {{ $book->description }}
                    </p>
                @else
                    <p class="mt-3 text-sm text-secondary">{{ __('book.no_synopsis') }}</p>
                @endif
            </section>

            {{-- Aksi --}}
            <div class="mt-9 border-t border-hairline pt-7">
                @auth
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($book->hasFile())
                            @if ($canRead)
                                {{-- Parameter `page` harus ikut di dalam array
                                     parameter, bukan sebagai argumen ketiga
                                     `route()`: argumen ketiga itu flag
                                     "absolute URL", jadi kalau dipakai untuk
                                     query string, `?page=`-nya hilang
                                     diam-diam dan tombolnya selalu membuka
                                     halaman 1. --}}
                                <a href="{{ $lastReadPage > 0
                                        ? route('books.read', ['book' => $book, 'page' => $lastReadPage])
                                        : route('books.read', $book) }}"
                                   class="btn btn-primary btn-lg">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                    </svg>
                                    {{ $lastReadPage > 0 ? __('book.continue_from_page', ['page' => $lastReadPage]) : __('book.read_book') }}
                                </a>
                            @else
                                <span class="btn btn-secondary btn-lg cursor-not-allowed opacity-60"
                                                                            title="{{ __('book.cannot_read') }}">
                                                                        {{ __('book.read_book') }}
                                </span>
                            @endif
                        @endif

                        {{-- Peminjaman mandiri oleh anggota (Task 10.1).
                             Status di bawah hanya untuk tampilan; aturan yang
                             sesungguhnya ditegakkan BorrowBook di dalam
                             transaksi, karena buku bisa saja sudah dipinjam
                             orang lain setelah halaman ini dirender. --}}
                        @if (auth()->user()->isMember())
                            @if ($borrowState['can'])
                                <form method="POST" action="{{ route('books.borrow', $book) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                                        </svg>
                                        {{ __('book.borrow') }}
                                    </button>
                                </form>
                            @else
                                <span class="btn btn-secondary btn-lg cursor-not-allowed opacity-60"
                                      title="{{ $borrowState['reason'] }}">
                                    {{ __('book.borrow') }}
                                </span>

                                {{-- Daftar tunggu (Task 24.6). Hanya muncul kalau
                                     satu-satunya alasan tombol pinjam mati adalah
                                     tidak ada eksemplar tersisa — kasus lain
                                     (buku tidak aktif / sedang dipinjam sendiri)
                                     memang ditolak server, jadi tidak ditawarkan. --}}
                                @if ($borrowState['queueable'])
                                    <form method="POST"
                                          data-ajax
                                          data-ajax-fallback
                                          data-waiting-toggle
                                          data-store-url="{{ route('waiting-lists.store', $book) }}"
                                          data-destroy-url="{{ route('waiting-lists.destroy', $book) }}"
                                          action="{{ $waitingListed ? route('waiting-lists.destroy', $book) : route('waiting-lists.store', $book) }}">
                                        @csrf
                                        @if ($waitingListed) @method('DELETE') @endif
                                        <button type="submit" class="btn btn-secondary btn-lg {{ $waitingListed ? 'text-tertiary' : '' }}" data-toggle-button
                                                data-label-add="{{ __('book.join_waiting') }}"
                                                data-label-remove="{{ __('book.leave_waiting') }}"
                                                aria-pressed="{{ $waitingListed ? 'true' : 'false' }}">
                                            <svg class="h-4 w-4" data-toggle-icon
                                                 fill="{{ $waitingListed ? 'currentColor' : 'none' }}"
                                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                            <span data-toggle-label>{{ $waitingListed ? __('book.leave_waiting') : __('book.join_waiting') }}</span>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        @elseif (auth()->user()->isStaff())
                            <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary btn-lg">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931ZM19.5 7.125 16.875 4.5" />
                                </svg>
                                {{ __('book.edit') }}
                            </a>
                            <a href="{{ route('loans.index') }}" class="btn btn-secondary btn-lg">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/>
                                </svg>
                                {{ __('book.record_loan') }}
                            </a>
                        @endif

                        @if (auth()->user()->isMember())
                            @php $isFavorite = in_array($book->id, $favoriteIds, true); @endphp

                            {{--
                                Form favorit (Task 14.7). `data-ajax` membuat form ini
                                dikirim lewat fetch dan tidak me-reload halaman; tanpa
                                JS ia tetap form POST biasa yang me-redirect, jadi tidak
                                ada jalur yang hilang.

                                action DAN method ikut berganti lewat `data-store-url` /
                                `data-destroy-url`, karena form yang sama harus bisa
                                melakukan tambah DAN hapus. Kalau hanya label tombolnya
                                yang berubah, klik kedua akan mengirim POST ke endpoint
                                yang salah.

                                `data-ajax-fallback` mengizinkan JS mengirim ulang lewat
                                jalur native kalau koneksi putus sebelum server
                                menjawab. Aman di sini karena toggle ini idempoten:
                                tambah dua kali tetap satu baris, hapus yang tidak ada
                                tidak merusak apa pun. Form lain TIDAK boleh memakai
                                atribut ini tanpa ditinjau dulu, karena request ulang
                                bisa jadi dobel.
                            --}}
                            <form method="POST"
                                  data-ajax
                                  data-ajax-fallback
                                  data-favorite-toggle
                                  data-store-url="{{ route('favorites.store', $book) }}"
                                  data-destroy-url="{{ route('favorites.destroy', $book) }}"
                                  action="{{ $isFavorite ? route('favorites.destroy', $book) : route('favorites.store', $book) }}">
                                @csrf
                                @if ($isFavorite) @method('DELETE') @endif
                                {{-- Kedua label ditulis di Blade, bukan ditulis ulang
                                     di JS. Kalau kalimat "Tambah ke favorit" ada di
                                     dua tempat, sooner or later salah satu tidak
                                     ikut diubah dan tombolnya berbohong. --}}
                                <button type="submit" class="btn btn-ghost {{ $isFavorite ? 'text-overdue' : '' }}" data-toggle-button
                                        data-label-add="{{ __('book.add_favorite') }}"
                                        data-label-remove="{{ __('book.remove_favorite') }}"
                                        aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                    <svg class="h-4 w-4" data-toggle-icon
                                         fill="{{ $isFavorite ? 'currentColor' : 'none' }}"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                                    </svg>
                                    <span data-toggle-label>{{ $isFavorite ? __('book.remove_favorite') : __('book.add_favorite') }}</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    @unless ($book->hasFile())
                        <p class="mt-4 text-sm text-secondary">
                            {{ __('book.digital_unavailable') }}
                        </p>
                    @endunless
                @else
                    <p class="text-sm text-secondary">
                        <a href="{{ route('login') }}" class="link-accent">{{ __('auth.login') }}</a>
                        {{ __('book.login_to_access') }}
                    </p>
                @endauth
            </div>
        </div>
    </div>

    {{-- Buku terkait --}}
    @if ($related->isNotEmpty())
        <section class="mt-16">
            <p class="section-eyebrow">Rekomendasi</p>
            <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">Buku Lainnya</h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($related as $item)
                    <x-book-card :book="$item" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
