@props([
    'user',
    // Ukuran yang dipakai: 'xs' (dropdown akun), 'sm' (sidebar), 'md'
    // (kartu profil), 'lg' (kartu statistik / daftar pengguna).
    'size' => 'sm',
    // Teks alternatif untuk pembaca layar. Default-nya nama user — nama adalah
    // informasi yang berguna, sedangkan avatar cuma dekorasi. Kalau pemanggil
    // memang sedang menampilkan nama di sebelahnya, pass `''` supaya gambar
    // tidak diumumkan dua kali.
    'alt' => null,
])

@php
    /*
     * Class ditulis utuh per ukuran, bukan dirangkai dari variabel
     * (`class="h-{{ $size }}"`). Tailwind memindai sumber sebagai teks, jadi
     * kelas yang hanya muncul di dalam interpolasi string tidak akan pernah
     * ikut ter-generate.
     */
    $sizes = [
        'xs' => 'h-8 w-8 text-label',
        'sm' => 'h-9 w-9 text-sm',
        'md' => 'h-16 w-16 text-lg',
        'lg' => 'h-24 w-24 text-2xl',
    ];

    $avatarClass = $sizes[$size] ?? $sizes['sm'];
    $avatarUrl = $user->avatarUrl();
    $initials = $user->initials();
@endphp

@if ($avatarUrl)
    <img src="{{ $avatarUrl }}"
         alt="{{ $alt ?? $user->name }}"
         loading="lazy"
         {{ $attributes->merge([
             'class' => $avatarClass.' shrink-0 rounded-full border border-hairline object-cover',
         ]) }}>
@else
    {{--
        Tanpa foto: lingkaran berisi inisial. `aria-hidden` karena teksnya
        bukan informasi baru — nama user sudah tertulis di sebelahnya, dan
        pembaca layar tidak perlu membaca "B" dua kali.
    --}}
    <span aria-hidden="true"
        {{ $attributes->merge([
            'class' => $avatarClass.' grid shrink-0 place-items-center rounded-full bg-tertiary/10 font-semibold text-tertiary',
        ]) }}>
        {{ $initials }}
    </span>
@endif