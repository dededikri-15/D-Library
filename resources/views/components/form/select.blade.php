@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'hint' => null,
    'allowEmpty' => true,
    'emptyLabel' => 'Semua',
])

{{--
    Select untuk form. $options boleh array sederhana (id => label) atau
    hasil pluck di controller.
--}}

    <div>
        <label for="{{ $name }}" class="field-label">
            {{ $label }}{{ $required ? ' *' : '' }}
        </label>

        <div class="flex items-stretch gap-2">
            <select id="{{ $name }}" name="{{ $name }}" @required($required)
                    @class([
                        'field-input',
                        'border-overdue focus:border-overdue focus:ring-overdue/30' => $errors->has($name),
                    ])>
                @if ($allowEmpty)
                    <option value="">{{ $emptyLabel }}</option>
                @endif

                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>

            @if (isset($attributes['data-quick-add']))
                @php
                    $modalId = match($attributes['data-quick-type']) {
                        'category' => 'modal-kategori',
                        'author' => 'modal-penulis',
                        'publisher' => 'modal-penerbit',
                    };
                @endphp
                <button type="button" data-modal-open="#{{ $modalId }}"
                        class="btn btn-secondary w-10 shrink-0 p-0" title="Tambah {{ strtolower($label) }} baru"
                        aria-label="Tambah {{ strtolower($label) }} baru">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
            @endif
        </div>

    @error($name)
        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
    @enderror

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-label text-secondary">{{ $hint }}</p>
    @endif
</div>
