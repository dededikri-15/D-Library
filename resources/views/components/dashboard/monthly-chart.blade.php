@props([
    'items',
    'title',
])

{{--
    Grafik batang jumlah peminjaman per bulan (Task 26.2).

    Dirender sepenuhnya di server sebagai HTML + CSS, bukan SVG yang digambar
    JS: dasbor harus tetap terbaca kalau JavaScript gagal dimuat, dan angkanya
    harus muncul di test tanpa perlu browser. Tidak ada library chart —
    proyek ini hanya memakai Blade, Tailwind, dan vanilla JS.

    Batang berbentuk div dengan persentase tinggi karena teks di dalam SVG
    ikut mengecil/membesar mengikuti viewBox, sedangkan di HTML ukuran huruf
    tetap. Di layar kecil 12 batang memang rapat, tapi angka pastinya selalu
    tersedia lewat tabel tersembunyi di bawah grafik.

    Warna batang memakai token `tertiary`, bukan hijau/kuning/merah: ketiga
    warna itu sudah dibooking untuk status buku (tersedia/dipinjam/terlambat)
    dan dipakai badge di halaman yang sama.
--}}
@php
    $total = (int) $items->sum('value');
    $max = max(1, (int) $items->max('value'));
    $hasData = $total > 0;
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <h2 class="font-semibold text-primary">{{ $title }}</h2>

        @if ($hasData)
            <span class="badge badge-muted tabular-nums">
                {{ __('dashboard.loans_chart_total', ['count' => number_format($total, 0, ',', '.')]) }}
            </span>
        @endif
    </div>

    @unless ($hasData)
        <x-empty-state class="mt-4"
                       :title="__('dashboard.loans_chart_empty')"
                       :description="__('dashboard.loans_chart_empty_description')" />
    @else
        {{--
            `role="img"` + `aria-label` membuat pembaca layar menyebut ringkasan
            grafik sekali dengar, bukan menghabiskan 12 batang tanpa arti.
            Label sumbu sengaja `aria-hidden` — isinya sudah ada di tabel
            tersembunyi, jadi kalau keduanya dibaca pengguna mendengar dua kali.
        --}}
        <div class="mt-5 flex h-44 items-end gap-1.5 border-b border-hairline"
             role="img"
             aria-label="{{ __('dashboard.loans_chart_alt', ['months' => $items->count(), 'count' => number_format($total, 0, ',', '.')]) }}">
            @foreach ($items as $item)
                @php
                    $height = (int) round($item['value'] / $max * 100);
                @endphp
                <div class="group flex h-full flex-1 items-end"
                     title="{{ $item['label'] }} — {{ $item['value'] }}">
                    <span class="min-h-[2px] w-full rounded-t-[3px] transition-colors {{ $item['value'] > 0 ? 'bg-tertiary/70 group-hover:bg-tertiary' : 'bg-secondary/25' }}"
                          style="height: {{ $height }}%"></span>
                </div>
            @endforeach
        </div>

        <div class="mt-2 flex gap-1.5" aria-hidden="true">
            @foreach ($items as $item)
                <span class="flex-1 text-center text-label text-secondary">{{ $item['short'] }}</span>
            @endforeach
        </div>

        <table class="sr-only">
            <caption>{{ $title }}</caption>
            <thead>
                <tr>
                    <th scope="col">{{ __('dashboard.chart_month') }}</th>
                    <th scope="col">{{ __('dashboard.chart_loans') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <th scope="row">{{ $item['label'] }}</th>
                        <td>{{ $item['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endunless
</div>
