@props([
    // Nama jenis data, mis. "kategori". Dipakai di kalimat dan di label tombol.
    'label',
    'name',
    'action',
    'booksCount' => 0,
])

@php
    $isUsed = $booksCount > 0;
@endphp

{{--
    Area hapus untuk halaman edit master data (kategori, penulis, penerbit).

    Dipisah dari form utama karena tiga alasan:

    1. Tidak boleh ada <form> di dalam <form>. Browser membuang tag form kedua
       beserta atributnya, dan field di dalamnya jadi milik form utama — yang
       membuat form utama tidak bisa dikirim sama sekali (bug yang sama seperti
       quick-add di Task 17.2).

    2. Tombolnya diberi gaya dan penempatan yang jelas berbeda dari tombol
       "Simpan perubahan". Menghapus data bukan varian dari menyimpan, dan
       letaknya berdampingan dengan tombol simpan akan membuat keduanya mudah
       tertukar.

    3. Jumlah buku yang IKUT terhapus ditulis di atas tombol, sebelum tombol
       diklik. Hapus master data berarti menghapus isi dan riwayatnya juga
       (`books.category_id` memakai `cascadeOnDelete()`), jadi orang berhak
       tahu itu sebelum menekan, bukan setelah melihat katalognya berkurang.

    Tanda kutip di `data-confirm` ditulis apa adanya (`"`), bukan `&quot;`:
    nilai attribute ini sudah keluar dari `{{ }}` sehingga `e()` yang
    meng-escape-nya, dan `&quot;` yang ditulis manual akan tampil apa adanya
    di dalam dialog.
--}}

<div class="mt-6 max-w-xl rounded-lg border border-overdue/30 bg-overdue/5 p-5">
    <h2 class="text-base font-semibold text-primary">Hapus {{ $label }}</h2>

    @if ($isUsed)
        <p class="mt-1 text-sm text-secondary">
            Hapus {{ $label }} ini beserta <strong class="font-semibold text-overdue">{{ $booksCount }} buku</strong>
            di dalamnya. Buku, file PDF, dan riwayat peminjamannya ikut terhapus permanen.
        </p>
    @else
        <p class="mt-1 text-sm text-secondary">
            Hapus {{ $label }} ini dari daftar. Buku yang sudah ada tidak terpengaruh, dan tindakan ini tidak bisa dibatalkan.
        </p>
    @endif

    <form method="POST" action="{{ $action }}" class="mt-4"
          data-confirm="{{ $isUsed
              ? 'Hapus ' . $label . ' "' . $name . '"? ' . $booksCount . ' buku ikut terhapus permanen, beserta file dan riwayat peminjamannya.'
              : 'Hapus ' . $label . ' "' . $name . '"? Tindakan ini tidak bisa dibatalkan.'
          }}"
          data-confirm-title="Hapus {{ $label }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Hapus {{ $label }}</button>
    </form>
</div>
