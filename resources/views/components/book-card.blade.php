@props(['book', 'modeParams' => []])

{{--
    Kartu buku untuk katalog, homepage, dan daftar "buku terkait".

    Semua kartu memakai komponen ini supaya perubahan gaya di satu tempat
    berlaku di semua halaman. Warna status tetap berasal dari <x-status-badge>,
    yang merupakan satu-satunya tempat yang memutuskan warna status.
--}}

<article {{ $attributes->merge([
    'class' => 'card group flex h-full flex-col overflow-hidden transition-all duration-300',
    // Naik sedikit + bayangan menguat saat hover, hanya di perangkat yang
    // benar-benar punya hover (layar sentuh tidak punya "hover").
    // `motion-reduce` mematikan transformasi untuk pengguna yang meminta
    'motion-safe:hover:-translate-y-1 motion-safe:hover:border-tertiary/40 motion-safe:hover:shadow-lift',
    'motion-reduce:transform-none',
]) }}>

    <a href="{{ route('books.show', $book) }}" class="relative block overflow-hidden">
        <x-book-cover :book="$book" />

        {{-- Lapisan gelap tipis di atas cover saat hover, supaya tautannya terasa hidup. --}}
        <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-primary/20 to-transparent opacity-0
                     transition-opacity duration-300 group-hover:opacity-100 motion-reduce:opacity-0"
              aria-hidden="true"></span>

        {{--
            Penanda file digital di sisi cover. Dua keadaan, dua penampilan,
            supaya pembaca tidak perlu menebak:
              • ada file  → pill solid "PDF online"
              • tidak ada → pill putus-putus "PDF belum tersedia" (abu, redup)
            Sengaja TIDAK memakai warna status (hijau/kuning/merah) supaya tidak
            diartikan sebagai ketersediaan pinjaman — di sini topiknya akses
            baca, bukan antrean. Label status tetap milik <x-status-badge>.
        --}}
        @if ($book->hasFile())
            <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-hairline-strong
                         bg-surface/90 px-2.5 py-1 text-label font-semibold text-primary shadow-card backdrop-blur"
                  title="{{ __('book.pdf_online_hint') }}">
                <svg class="h-3.5 w-3.5 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.63c0-1.14-.46-2.23-1.28-3.03l-2.4-2.4a4.28 4.28 0 0 0-3.04-1.26H7.5m10.5 9.04v2.63c0 1.14-.46 2.23-1.28 3.03l-2.4 2.4a4.28 4.28 0 0 1-3.04 1.26H7.5m10.5-9.04H4.5a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5h3.09a1.5 1.5 0 0 0 1.06-.44l1.69-1.7a1.5 1.5 0 0 1 1.06-.44h3.6a1.5 1.5 0 0 1 1.5 1.5v3.09a1.5 1.5 0 0 0 .44 1.06l1.7 1.69a1.5 1.5 0 0 0 1.06.44h1.5a1.5 1.5 0 0 1 1.5 1.5Z"/>
                </svg>
                {{ __('book.pdf_online') }}
            </span>
        @else
            <span class="absolute top-3 right-3 inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-dashed
                         border-hairline-strong bg-surface/70 px-2.5 py-1 text-label font-medium text-secondary backdrop-blur"
                  title="{{ __('book.pdf_missing_hint') }}">
                <svg class="h-3.5 w-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19.5 14.25v-2.63c0-1.14-.46-2.23-1.28-3.03l-2.4-2.4a4.28 4.28 0 0 0-3.04-1.26H7.5m10.5 9.04v2.63c0 1.14-.46 2.23-1.28 3.03l-2.4 2.4a4.28 4.28 0 0 1-3.04 1.26H7.5m10.5-9.04H4.5a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5h3.09a1.5 1.5 0 0 0 1.06-.44l1.69-1.7a1.5 1.5 0 0 1 1.06-.44h3.6a1.5 1.5 0 0 1 1.5 1.5v3.09a1.5 1.5 0 0 0 .44 1.06l1.7 1.69a1.5 1.5 0 0 0 1.06.44h1.5a1.5 1.5 0 0 1 1.5 1.5Z"/>
                </svg>
                {{ __('book.pdf_missing') }}
            </span>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($book->category)
            {{--
                `modeParams` hanya berisi `lihat=katalog` saat kartu ini dirender
                di halaman katalog milik staf. Tanpanya, mengklik chip kategori
                akan membuang parameter itu dan staf tiba-tiba pindah ke mode
                kelola padahal tadinya sedang membaca katalog.
            --}}
            <a href="{{ route('books.index', array_merge($modeParams, ['category' => $book->category->slug])) }}"
               class="badge border border-tertiary/25 bg-tertiary/10 text-tertiary transition-colors
                      hover:border-tertiary/50 hover:bg-tertiary/15 dark:bg-tertiary/15">
                {{ $book->category->name }}
            </a>
        @endif

        <h3 class="mt-2.5 line-clamp-2 font-semibold text-primary">
            <a href="{{ route('books.show', $book) }}" class="transition-colors group-hover:text-tertiary">
                {{ $book->title }}
            </a>
        </h3>

        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-secondary">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.1a7.5 7.5 0 0 1 15 0 17.9 17.9 0 0 1-7.5 1.9 17.9 17.9 0 0 1-7.5-1.9Z"/>
            </svg>
            <span class="truncate">{{ $book->author?->name ?? 'Penulis tidak diketahui' }}</span>
        </p>

        <div class="mt-auto flex flex-wrap items-center justify-between gap-2 pt-3.5">
            <x-status-badge :status="$book->status" />

            <span class="text-label tabular-nums text-secondary">
                {{ $book->publication_year ?? '—' }}
            </span>
        </div>

        {{--
            Slot aksi di dalam kartu. Katalog memakainya untuk "Pratinjau cepat"
            dan tombol Edit, supaya tombol yang membuka kartu ini tidak terpisah
            jadi elemen tersendiri di bawahnya. Halaman lain yang tidak mengirim
            isi slot tetap mendapat kartu tanpa bagian ini (`$slot` tidak pernah
            null pada komponen Blade, jadi yang dicek kekosongannya).
        --}}
        @if (! $slot->isEmpty())
            <div class="mt-3 space-y-2 border-t border-hairline pt-3">
                {{ $slot }}
            </div>
        @endif
    </div>
</article>
