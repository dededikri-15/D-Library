@props([
    'title',
    'href' => null,
    'linkLabel' => null,
])

{{--
    Kartu panel berisi daftar: judul di kiri atas, opsional tautan
    "lihat semua →" di kanan atas, isi daftar disediakan lewat slot.
    Margin atas (mis. mt-8) dilewatkan lewat atribut `class` supaya
    kartu pertama dan kartu berikutnya bisa diberi jarak berbeda.
--}}

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-semibold text-primary">{{ $title }}</h2>

        @if ($href)
            <a href="{{ $href }}" class="text-sm font-medium text-tertiary hover:underline">
                {{ $linkLabel }} &rarr;
            </a>
        @endif
    </div>

    {{ $slot }}
</div>
