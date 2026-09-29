@props(['max' => 4])

{{--
    Wilayah toast (Task 14.5).

    Satu elemen tetap untuk seluruh halaman, toast yang baru ditumpangkan di
    sini. Alasannya `aria-live` diletakkan pada elemen ini: pembaca layar hanya
    membacakan notifikasi kalau live region-nya sudah ada SEBELUM isinya
    berubah. Kalau atributnya menempel pada toast-nya sendiri, toast yang dibuat
    setelah halaman selesai dimuat tidak akan pernah diumumkan.

    Empat `<template>` di bawah dipakai `window.toast` di `app.js` untuk
    mengklon markup toast — termasuk ikon dan warna status — tanpa menulis
    ulang HTML-nya sebagai string di JavaScript.
--}}

<div data-toast-region data-toast-max="{{ $max }}" class="toast-region" role="region" aria-label="Notifikasi"
    aria-live="polite">
    {{ $slot }}

    @foreach (['success', 'error', 'warning', 'info'] as $variant)
        <template data-toast-template="{{ $variant }}">
            <x-alert :variant="$variant" class="toast" auto-dismiss>
                <span data-toast-body></span>
            </x-alert>
        </template>
    @endforeach
</div>
