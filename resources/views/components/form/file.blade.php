@props([
    'name',
    'label',
    'accept' => null,
    'mimes' => [],
    'current' => null,
    'currentUrl' => null,
    'removeName' => null,
    'maxKb' => null,
    'hint' => null,
])

{{--
    Input upload berkas.

    Cover/foto penulis tampil di katalog sehingga pratinjau gambarnya
    ditampilkan. PDF bersifat privat, jadi tidak ada pratinjau gambar —
    cukup nama berkas dan penanda format.
--}}

@php
    $isImage = str_ends_with((string) $accept, 'image/*') || in_array($name, ['cover', 'photo'], true);
@endphp

<div>
    <label for="{{ $name }}" class="field-label">{{ $label }}</label>

    <input type="file"
           id="{{ $name }}"
           name="{{ $name }}"
           @if ($accept) accept="{{ $accept }}" @endif
           @class([
               'field-input file:mr-3 file:rounded-sm file:border-0 file:bg-tertiary/10 file:px-3 file:py-1.5 file:text-label file:font-medium file:text-tertiary',
               'border-overdue' => $errors->has($name),
           ])>

    @if (filled($mimes) && filled($maxKb))
        <p class="mt-1 text-label text-secondary">
            {{ __('book_form.format_limit', [
                'formats' => strtoupper(implode(', ', $mimes)),
                'size' => number_format($maxKb / 1024, $maxKb % 1024 === 0 ? 0 : 1),
            ]) }}
        </p>
    @endif

    @if ($hint)
        <p class="mt-1 text-label text-secondary">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
    @enderror

    @if (filled($current))
        <div class="mt-3 flex items-center gap-3 rounded-lg border border-secondary/20 bg-secondary/5 p-3">
            @if ($isImage && filled($currentUrl))
                <img src="{{ $currentUrl }}" alt="{{ __('book_form.preview', ['label' => $label]) }}"
                     class="h-16 w-12 shrink-0 rounded-sm border border-secondary/20 object-cover">
            @else
                <span class="flex h-16 w-12 shrink-0 items-center justify-center rounded-sm border border-secondary/20 text-label text-secondary">
                    PDF
                </span>
            @endif

            <div class="min-w-0 flex-1 text-sm">
                <p class="text-primary">{{ __('book_form.uploaded') }}</p>
                <p class="truncate text-label text-secondary">{{ $current }}</p>
            </div>

            @if (filled($removeName))
                <label class="flex shrink-0 cursor-pointer items-center gap-2 text-sm text-secondary transition-colors hover:text-overdue">
                    <input type="checkbox" name="{{ $removeName }}" value="1"
                           class="rounded-sm border-secondary/30 text-overdue focus:ring-overdue/30">
                    {{ __('book_form.remove_file') }}
                </label>
            @endif
        </div>

        @if ($isImage)
            <p class="mt-1 text-label text-secondary">{{ __('book_form.replace_image') }}</p>
        @endif
    @endif
</div>
