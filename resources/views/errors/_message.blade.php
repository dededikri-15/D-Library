@php
    /*
     * Satu partial untuk semua halaman error. Dipakai errors/403, 404, 419,
     * 429, dan 500 supaya pengguna tidak melihat lima halaman dengan gaya
     * berbeda untuk kode yang sebenarnya kasusnya sama.
     */
    $errorKey = match ((int) ($code ?? 0)) {
        403 => '403',
        404 => '404',
        419 => '419',
        429 => '429',
        503 => '503',
        default => 'default',
    };
@endphp

<div class="mx-auto max-w-md py-16 text-center">
    <p class="text-display font-semibold text-tertiary">{{ $code }}</p>
    <h1 class="mt-4 text-h1 font-semibold text-primary">{{ __('errors.'.$errorKey.'_title') }}</h1>
    <p class="mt-3 text-body text-secondary">{{ __('errors.'.$errorKey.'_message') }}</p>

    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ route('home') }}" class="btn btn-primary">{{ __('errors.home') }}</a>
        <a href="{{ route('books.index') }}" class="btn btn-secondary">{{ __('errors.catalog') }}</a>
    </div>
</div>
