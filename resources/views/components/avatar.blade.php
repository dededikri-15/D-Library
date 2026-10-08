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
    $userNumber = abs((int) $user->getKey());
    $malePortraits = [0, 2, 4, 6, 8, 11, 13, 15, 17, 19];
    $femalePortraits = [1, 3, 5, 7, 9, 10, 12, 14, 16, 18];
    $portraitOptions = match ($user->gender) {
        \App\Models\User::GENDER_LAKI_LAKI => $malePortraits,
        \App\Models\User::GENDER_PEREMPUAN => $femalePortraits,
        default => $userNumber % 2 === 0 ? $malePortraits : $femalePortraits,
    };
    $avatarIndex = $portraitOptions[intdiv($userNumber, 2) % count($portraitOptions)];
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
        Fallback avatar: potret gender, satu file PNG per indeks di
        `public/images/avatar-portraits/`. Dulu memakai satu sprite 5x4
        dengan `background-position` — tapi grid sprite tidak presisi dan
        cakramnya cuma ~84% lebar sel, jadi potret tidak pernah pas ke
        bulat. Sekarang tiap potret sudah dipotong pas selebar cakramnya
        (isi 97-99% dari file), sehingga `object-cover` membuatnya tepat
        memenuhi lingkaran. `alt=""` + aria-hidden: ini dekoratif.
    --}}
    <img src="{{ asset('images/avatar-portraits/'.$avatarIndex.'.png') }}"
         alt=""
         aria-hidden="true"
         loading="lazy"
         {{ $attributes->merge([
             'class' => $avatarClass.' shrink-0 rounded-full border border-hairline object-cover',
         ]) }}>
@endif
