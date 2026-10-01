@props([
    'label' => null,
    'rows' => 3,
    'as' => 'div',
])

{{--
    Loading state / skeleton (Task 14.8).

    INI ADALAH SATU-SATUNYA sumber markup skeleton di proyek. JavaScript TIDAK
    menyusun skeleton sendiri: ia mengklon isi `<template>` yang dirender
    komponen ini, persis seperti yang dilakukan Task 14.5 untuk toast. Kalau
    bentuk skeleton ditulis ulang sebagai string di app.js, perubahan di sini
    tidak akan pernah muncul di panel pencarian — dan tidak ada satu pun test
    yang gagal, karena HTML-nya memang sama-sama valid.

    Bentuk visualnya (tinggi baris, radius, warna) ada di `.skeleton-*` di
    resources/css/app.css, bukan di utility inline di sini. Class utility
    hanya menyusun strukturnya.

    Pemakaian:

        <template data-search-skeleton>
            <x-loading-skeleton :rows="3" />
        </template>

    `as="template"` dipakai untuk membungkus kartu di dalam `<template>`: isi
    `<template>` tidak pernah tampil dan tidak boleh dihitung screen reader,
    jadi atribut `role`/`aria-*`-nya hanya di isi, bukan di pembungkus.
--}}

<{{ $as }} @attributes>
    <p class="skeleton-caption" role="status" aria-live="polite">
        <span class="skeleton-spinner" aria-hidden="true"></span>
        {{ $label ?? __('shared.loading') }}
    </p>

    <div class="mt-2 space-y-3" aria-hidden="true">
        @for ($i = 0; $i < $rows; $i++)
            <div class="skeleton">
                <div class="skeleton-cover animate-pulse"></div>
                <div class="skeleton-lines">
                    <div class="skeleton-line animate-pulse"></div>
                    <div class="skeleton-line skeleton-line-half animate-pulse"></div>
                </div>
            </div>
        @endfor
    </div>
</{{ $as }}>
