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

    3. Status "masih dipakai" ditampilkan SEBELUM tombol diklik. Server tetap
       menolak hapus (lihat `Controller::destroyMasterData()`), tapi tampilannya
       membuat orang tahu harus membereskan buku-bukunya dulu, bukan mengulang
       aksi yang sama.

    Tombol yang dinonaktifkan tidak dapat dikirim sama sekali — form tanpa
    tombol submit aktif tidak bisa disubmit, termasuk lewat tombol Enter.
--}}

<div class="mt-6 max-w-xl rounded-lg border border-overdue/30 bg-overdue/5 p-5">
    <h2 class="text-base font-semibold text-primary">Hapus {{ $label }}</h2>

    @if ($isUsed)
        <p class="mt-1 text-sm text-secondary">
            {{ ucfirst($label) }} ini masih dipakai {{ $booksCount }} buku, jadi belum bisa dihapus.
            Pindahkan dulu buku-buku tersebut ke {{ $label }} lain.
        </p>
    @else
        <p class="mt-1 text-sm text-secondary">
            Hapus {{ $label }} ini dari daftar. Buku yang sudah ada tidak terpengaruh, dan tindakan ini tidak bisa dibatalkan.
        </p>
    @endif

    <form method="POST" action="{{ $action }}" class="mt-4"
          data-confirm="Hapus {{ $label }} &quot;{{ $name }}&quot;? Tindakan ini tidak bisa dibatalkan."
          data-confirm-title="Hapus {{ $label }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger" @disabled($isUsed)>Hapus {{ $label }}</button>
    </form>
</div>
