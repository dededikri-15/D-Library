@props([
    'id',
    'title' => null,
    'size' => 'md',
    'closeLabel' => 'Tutup',
    // `alertdialog` untuk konfirmasi destruktif, `dialog` untuk isi biasa.
    'role' => 'dialog',
])

{{--
    Modal (Task 14.3).

    Dibangun di atas elemen `<dialog>` asli: focus trap, Escape, dan
    `inert` untuk konten di belakang sudah ditangani browser, jadi tidak
    perlu menirukan manual (dan biasanya salah).

    Pemakaian:

        <x-modal id="konfirmasi-hapus" title="Hapus buku?">Isi</x-modal>
        <button type="button" data-modal-open="#konfirmasi-hapus">Buka</button>

    Dialog dirender dalam keadaan tertutup (tanpa atribut `open`), jadi
    isinya tidak akan pernah terlihat sebelum JS membukanya. Tombol pembuka
    cukup menulis `data-modal-open="#id"`, yang ditangani `initModals()`
    di `resources/js/app.js`.

    `data-modal-title` sengaja ada di `<h2>` supaya JS bisa mengganti judulnya
    tanpa searching atribut `*-judul` yang bentuknya bisa berubah.
--}}

<dialog id="{{ $id }}" data-modal role="{{ $role }}" aria-labelledby="{{ $id }}-judul" @class([
    'modal',
    'modal-sm' => $size === 'sm',
    'modal-lg' => $size === 'lg',
])>
    <div class="sticky top-0 flex items-start gap-4 border-b border-hairline bg-surface px-5 py-4">
        <h2 id="{{ $id }}-judul" data-modal-title class="min-w-0 flex-1 text-lg font-semibold text-balance text-primary">
            {{ $title }}
        </h2>
        <button type="button" data-modal-close class="btn btn-ghost h-9 w-9 shrink-0 p-0" title="{{ $closeLabel }}"
            aria-label="{{ $closeLabel }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="px-5 py-5">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-hairline bg-surface px-5 py-3.5">
            {{ $footer }}
        </div>
    @endisset
</dialog>
