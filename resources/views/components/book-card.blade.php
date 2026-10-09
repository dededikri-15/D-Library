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

        @if ($book->hasFile())
            <span class="absolute top-3 right-3 badge bg-primary/80 text-on-brand backdrop-blur-sm">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-2"/>
                </svg>
                PDF
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
    </div>
</article>
