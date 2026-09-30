@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'autocomplete' => null,
    // Override id elemen. Default-nya sama dengan `name`, tapi field yang
    // namanya cuma "name" (form quick-add kategori/penulis/penerbit) butuh
    // id sendiri: tiga form itu ada di satu halaman, dan id yang sama tiga
    // kali itu merusak <label for> — klik label tidak akan memfokuskan input.
    'id' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

{{--
    Satu field form. Dipakai juga untuk menampilkan pesan error per-field,
    bukan hanya daftar error di atas halaman — user langsung tahu field mana
    yang salah.
--}}

<div>
    <label for="{{ $inputId }}" class="field-label">
        {{ $label }}{{ $required ? ' *' : '' }}
    </label>

    <input id="{{ $inputId }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
           @required($required)
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @class([
              'field-input',
              'border-overdue focus:border-overdue focus:ring-overdue/30' => $errors->has($name),
          ])>

    @error($name)
        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
    @enderror

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-label text-secondary">{{ $hint }}</p>
    @endif
</div>
