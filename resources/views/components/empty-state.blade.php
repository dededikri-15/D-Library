@props([
    'title',
    'description' => null,
    'icon' => 'kosong',
])

{{--
    Empty state seragam untuk semua daftar. Tanpa ini, tiap view menulis
    "Belum ada data" dengan gaya berbeda-beda.

    Garis putus-putus + latar sedikit lebih redup dari kartu supaya state
    "tidak ada apa-apa di sini" terbaca tanpa perlu ikon besar yang mencolok.
--}}

<div {{ $attributes->merge([
    'class' => 'rounded-lg border border-dashed border-hairline-strong bg-surface/50 px-6 py-16 text-center',
]) }}>
    @if ($icon !== 'kosong')
        <p class="text-3xl" aria-hidden="true">{{ $icon }}</p>
    @else
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-secondary/10 text-secondary"
              aria-hidden="true">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
            </svg>
        </span>
    @endif

    <p class="mt-4 font-semibold text-primary">{{ $title }}</p>

    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm text-secondary">{{ $description }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-6 flex flex-wrap justify-center gap-3">{{ $slot }}</div>
    @endif
</div>
