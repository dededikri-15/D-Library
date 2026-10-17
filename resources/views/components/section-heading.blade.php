@props([
    'eyebrow' => null,
    'title',
    'description' => null,
])

{{--
    Kepala bagian halaman: baris "eyebrow" kecil di atas judul, opsional
    deskripsi di bawahnya. Dipakai di halaman beranda dan detail buku
    supaya jarak serta gaya tipografinya seragam.

    Komponen ini membungkus hasilnya dalam satu `<div>` polos tanpa kelas,
    persis seperti markup lama — supaya saat dipakai di dalam flex container
    ("eyebrow + judul + link kanan") teks tetap terkelompok sebagai SATU
    flex child dan tata letak responsifnya tidak berubah sama sekali.
--}}

<div>
    @if ($eyebrow)
        <p class="section-eyebrow">{{ $eyebrow }}</p>
    @endif

    <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">{{ $title }}</h2>

    @if ($description)
        <p class="mt-1.5 text-sm text-secondary">{{ $description }}</p>
    @endif
</div>
