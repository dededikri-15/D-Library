@props(['book'])

@php
    $modalId = 'pratinjau-buku-' . $book->id;
    $excerpt = $book->description
        ? \Illuminate\Support\Str::limit($book->description, 280)
        : __('shared.synopsis_unavailable');
@endphp

{{--
    Pratinjau cepat buku (Task 14.3).

    Satu-satunya alasan modal ini ada: user bisa melihat sinopsis, status, dan
    metadata tanpa kehilangan posisi di daftar katalog. Kalau ia memang mau
    membaca atau meminjam, ada tombol ke halaman detail.

    Dialog-nya dirender sekali per buku di dalam halaman, bukan diambil lewat
    fetch, supaya pratinjau tetap muncul walaupun JavaScript belum sempat
    selesai dimuat — dan supaya Task 14.7 (AJAX) tidak ikut berubah di sini.
--}}

<button type="button" data-modal-open="#{{ $modalId }}" class="btn btn-ghost btn-sm w-full">
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
    </svg>
    {{ __('shared.quick_preview') }}
</button>

<x-modal :id="$modalId" size="lg" :title="$book->title">
    <div class="grid gap-5 sm:grid-cols-3">
        <div class="sm:col-span-1">
            <x-book-cover :book="$book" size="lg" class="shadow-card ring-1 ring-hairline" />
        </div>

        <div class="sm:col-span-2">
            <div class="flex flex-wrap items-center gap-2">
                <x-status-badge :status="$book->status" />
                @if ($book->category)
                    <a href="{{ route('books.index', ['category' => $book->category->slug]) }}"
                       class="badge border border-tertiary/25 bg-tertiary/10 text-tertiary transition-colors
                              hover:border-tertiary/50 hover:bg-tertiary/15 dark:bg-tertiary/15">
                        {{ $book->category->name }}
                    </a>
                @endif
            </div>

            <p class="mt-3 text-sm text-secondary">
                {{ $book->author?->name ?? __('shared.unknown_author') }}
                @if ($book->publication_year)
                    <span aria-hidden="true">&middot;</span>
                    <span class="tabular-nums">{{ $book->publication_year }}</span>
                @endif
            </p>

            {{-- `whitespace-pre-line` dipakai karena sinopsis ditulis dengan
                 paragraf baru di textarea, sedangkan Blade meng-escape HTML. --}}
            <p class="mt-4 text-sm leading-relaxed whitespace-pre-line text-secondary">
                {{ $excerpt }}
            </p>

            <dl class="mt-5 grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                @foreach ([
                    __('shared.publisher') => $book->publisher?->name ?? '-',
                    __('shared.isbn') => $book->isbn,
                    __('shared.pages') => $book->pages ?? '-',
                    __('shared.published') => $book->publication_year ?? '-',
                ] as $label => $value)
                    <div class="flex items-center justify-between gap-3 border-b border-hairline pb-1.5">
                        <dt class="text-secondary">{{ $label }}</dt>
                        <dd class="truncate font-medium text-primary tabular-nums">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" data-modal-close class="btn btn-secondary">{{ __('shared.close') }}</button>
        <a href="{{ route('books.show', $book) }}" class="btn btn-primary">
            {{ __('shared.view_details') }}
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </a>
    </x-slot:footer>
</x-modal>
