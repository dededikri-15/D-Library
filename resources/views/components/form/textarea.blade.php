@props([
    'name',
    'label',
    'value' => null,
    'required' => false,
    'hint' => null,
    'rows' => 3,
])

<div>
    <label for="{{ $name }}" class="field-label">
        {{ $label }}{{ $required ? ' *' : '' }}
    </label>

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
              @required($required)
              @class([
                  'field-input',
                  'border-overdue focus:border-overdue focus:ring-overdue/30' => $errors->has($name),
              ])>{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
    @enderror

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-label text-secondary">{{ $hint }}</p>
    @endif
</div>
