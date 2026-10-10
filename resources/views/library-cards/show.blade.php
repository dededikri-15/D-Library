@extends('layouts.app')

@section('title', __('navigation.library_card').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('navigation.library_card') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('member.library_card_subtitle') }}</p>

    {{--
        Kartu perpustakaan digital.

        Desain meniru kartu fisik: rasio mendekati kartu kredit (1.586:1),
        chip dekoratif di kiri atas, dan garis-garis halus sebagai "orisinal".

        Warna memakai token `bg-hero` + `text-on-brand` — sama dengan CTA
        tamu di beranda, jadi kartu ini adalah satu dari sedikit elemen yang
        sengaja menonjol dari latar biasa.

        `print:hidden` pada tombol cetak supaya hanya kartunya yang keluar
        saat user menekan Ctrl+P.
    --}}
    <div class="mt-6 max-w-xl">

        {{--
            Kartu. `aspect-[1.586/1]` = rasio ISO/IEC 7810 ID-1 (kartu ATM /
            KTP). Isi kartu memakai `flex flex-col justify-between` supaya
            tiga blok (kepala, nomor, kaki) menyebar rapi di tinggi kartu.
        --}}
        <div class="relative aspect-[1.586/1] w-full overflow-hidden rounded-2xl bg-hero text-on-brand shadow-lift">

            {{-- Ornamen: dua lingkaran besar transparan di pojok kanan atas & bawah kiri --}}
            <div class="pointer-events-none absolute -top-16 -right-16 h-48 w-48 rounded-full bg-white/10" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-12 h-44 w-44 rounded-full bg-white/5" aria-hidden="true"></div>

            {{-- Garis-garis halus diagonal sebagai tekstur "orisinal" --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.04]"
                 style="background-image: repeating-linear-gradient(45deg, white 0 1px, transparent 1px 12px)"
                 aria-hidden="true"></div>

            {{-- Isi kartu --}}
            <div class="relative flex h-full flex-col justify-between p-5 sm:p-7">

                {{-- Kepala: nama perpustakaan + chip --}}
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[0.65rem] font-semibold tracking-[0.2em] uppercase opacity-80">
                            {{ config('app.name') }}
                        </p>
                        <p class="mt-0.5 text-[0.65rem] tracking-wide uppercase opacity-50">
                            {{ __('member.library_card_label') }}
                        </p>
                    </div>

                    {{-- Chip dekoratif (meniru chip NFC/kartu) --}}
                    <div class="flex h-9 w-11 shrink-0 items-center justify-center rounded-md border border-white/25 bg-white/15" aria-hidden="true">
                        <svg class="h-5 w-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8.25 6.75h12M8.25 12h12M8.25 17.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                    </div>
                </div>

                {{-- Tengah: nomor kartu --}}
                <div class="mt-4">
                    <p class="text-[0.65rem] tracking-wide uppercase opacity-50">{{ __('member.card_number') }}</p>
                    <p class="mt-1 font-mono text-xl font-bold tracking-[0.25em] tabular-nums sm:text-2xl">
                        {{ $card->card_number }}
                    </p>
                </div>

                {{-- Kaki: avatar + nama + masa berlaku --}}
                <div class="mt-4 flex items-end justify-between gap-3 border-t border-white/15 pt-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-avatar :user="$card->user" size="sm" alt="" class="border-white/25" />
                        <div class="min-w-0">
                            <p class="text-[0.6rem] tracking-wide uppercase opacity-50">{{ __('member.card_holder') }}</p>
                            <p class="truncate text-sm font-semibold">{{ $card->user->name }}</p>
                        </div>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="text-[0.6rem] tracking-wide uppercase opacity-50">{{ __('member.card_valid_until') }}</p>
                        <p class="mt-0.5 text-sm font-semibold tabular-nums">
                            {{ $card->valid_until->translatedFormat('d M Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status validitas --}}
        @if ($card->isValid())
            <p class="mt-4 flex items-center gap-1.5 text-sm text-available">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
                {{ __('member.card_active') }}
            </p>
        @else
            <p class="mt-4 flex items-center gap-1.5 text-sm text-overdue">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008Z"/>
                </svg>
                {{ __('member.card_expired') }}
            </p>
        @endif

        <p class="mt-4 max-w-prose text-sm text-secondary">
            {{ __('member.library_card_hint') }}
        </p>

        {{-- Tombol cetak --}}
        <button type="button" onclick="window.print()"
                class="btn btn-secondary btn-sm mt-4 print:hidden">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096M17.28 13.829L18.66 18m-2.14-8.171a42.415 42.415 0 0 0-10.56 0m10.56 0L17.28 18M6.72 13.829V5.146a2.25 2.25 0 0 1 2.25-2.25h6.06a2.25 2.25 0 0 1 2.25 2.25v8.683"/>
            </svg>
            {{ __('member.print_card') }}
        </button>
    </div>

    {{-- CSS cetak: hanya kartu yang dicetak, sisanya disembunyikan --}}
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .aspect-\[1\.586\/1\],
            .aspect-\[1\.586\/1\] * {
                visibility: visible;
            }
            .aspect-\[1\.586\/1\] {
                position: absolute;
                top: 0;
                left: 0;
                width: 85.6mm;
                box-shadow: none !important;
            }
        }
    </style>
@endsection
