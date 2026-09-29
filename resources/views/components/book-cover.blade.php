@props([
    'book',
    // Ukuran memengaruhi rasio, ukuran ikon, dan berapa detail yang ditulis.
    // 'sm' untuk kartu katalog, 'lg' untuk halaman detail.
    'size' => 'sm',
    // Judul & penulis ditulis di dalam placeholder saat cover kosong.
    'showMeta' => true,
])

@php
    $isLarge = $size === 'lg';
@endphp

{{--
    Cover buku: gambar asli kalau ada, kalau tidak placeholder bergradasi
    dengan ikon buku.

    Placeholder lama hanya kotak abu-abu bertuliskan "Cover belum tersedia",
    yang terlihat seperti aset gagal dimuat. Gradasi + ikon supaya kartu tetap
    terasa utuh, bukan rusak. Warnanya diambil dari token `--cover-*` yang
    sudah punya nilai untuk dark mode, jadi gradasi tidak menyilaukan di tema
    gelap.

    Nama class ditulis utuh (tidak dirangkai dari variabel) karena Tailwind
    memindai sumber sebagai teks. `text-{{ $isLarge ? 'sm' : 'label' }}` tidak
    akan pernah menghasilkan CSS apa pun.
--}}

@if ($book->cover)
    <img src="{{ Storage::url($book->cover) }}"
         alt="Cover {{ $book->title }}"
         loading="lazy"
         {{-- `merge`, bukan `{{ $attributes }}` polos: kalau pemanggil mengirim
              `class`, dicetak apa adanya akan jadi atribut `class` kedua dan
              browser diam-diam mengabaikannya. --}}
         {{ $attributes->merge(['class' => 'w-full rounded-lg object-cover aspect-3/4']) }}>
@else
    <div {{ $attributes->merge([
        'class' => 'bg-cover-placeholder relative flex w-full flex-col items-center justify-center overflow-hidden rounded-lg text-center ' .
            ($isLarge ? 'px-6 py-10' : 'aspect-3/4 px-3'),
    ]) }}
        role="img"
        aria-label="Cover belum tersedia untuk {{ $book->title }}">

        {{-- "Tulang" buku di sisi kiri, memberi ilusi buku punya tebal. --}}
        <span class="absolute inset-y-0 left-0 w-1.5" style="background-color: var(--cover-spine)" aria-hidden="true"></span>

        {{-- Cahaya halus di sudut kanan atas supaya gradasi tidak terasa datar. --}}
        <span class="absolute -top-8 -right-8 h-24 w-24 rounded-full bg-white/20 blur-xl dark:bg-white/10"
              aria-hidden="true"></span>

        <svg @class([
            'relative shrink-0',
            'h-8 w-8' => ! $isLarge,
            'h-14 w-14' => $isLarge,
        ])
             style="color: var(--cover-ink)"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75"/>
        </svg>

        @if ($showMeta)
            <p @class([
                'relative mt-3 line-clamp-2 leading-snug font-semibold',
                'text-label' => ! $isLarge,
                'text-body' => $isLarge,
            ])
               style="color: var(--cover-ink)">
                {{ $book->title }}
            </p>

            @if ($isLarge && $book->author)
                <p class="relative mt-1 text-label opacity-75" style="color: var(--cover-ink)">
                    {{ $book->author->name }}
                </p>
            @endif
        @endif

        <p @class([
            'relative mt-2 font-medium tracking-wide uppercase opacity-60',
            'text-[0.625rem]' => ! $isLarge,
            'text-label' => $isLarge,
        ])
           style="color: var(--cover-ink)">
            Tanpa cover
        </p>
    </div>
@endif
