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

    @error($name)
        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
    @enderror

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-label text-secondary">{{ $hint }}</p>
    @endif
</div>
