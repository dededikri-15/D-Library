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
    <h2 class="text-base font-semibold text-primary">{{ __('master.delete_entity', ['entity' => $label]) }}</h2>

    @if ($isUsed)
        <p class="mt-1 text-sm text-secondary">
            {{ __('master.delete_used_description', ['entity' => $label, 'count' => $booksCount]) }}
        </p>
    @else
        <p class="mt-1 text-sm text-secondary">
            {{ __('master.delete_unused_description', ['entity' => $label]) }}
        </p>
    @endif

    <form method="POST" action="{{ $action }}" class="mt-4"
          data-confirm="{{ $isUsed
              ? __('master.delete_question_used', ['entity' => $label, 'name' => $name, 'count' => $booksCount])
              : __('master.delete_question_unused', ['entity' => $label, 'name' => $name])
          }}"
          data-confirm-title="{{ __('master.delete_entity', ['entity' => $label]) }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">{{ __('master.delete_button', ['entity' => $label]) }}</button>
    </form>
</div>
