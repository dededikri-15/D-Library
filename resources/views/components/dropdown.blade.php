@props([
    'align' => 'right',
    'menuLabel' => 'Menu',
    'triggerClass' => 'h-10 px-1.5 sm:px-2.5',
])

{{--
    Dropdown generik (Task 14.2).

    Pemicu + panelnya selalu berdampingan di dalam satu wrapper `relative`,
    karena `initDropdowns()` mencari panel lewat `trigger.parentElement`.
    Status buka/tutup diurus JS lewat atribut `hidden`; Blade cukup menulis
    `hidden` supaya menu tidak melayang sebelum JS siap.

    Pemakaian:

        <x-dropdown align="right" menu-label="Menu akun">
            <x-slot:trigger>Isi tombol pemicu</x-slot:trigger>
            <x-slot:menu>Isi panel</x-slot:menu>
        </x-dropdown>
--}}

<div class="relative shrink-0">
    <button type="button" data-dropdown class="dropdown-trigger {{ $triggerClass }}" aria-expanded="false"
        aria-haspopup="menu">
        {{ $trigger }}
    </button>

    <div data-dropdown-menu role="menu" aria-label="{{ $menuLabel }}" hidden
        @class([
            'dropdown-menu',
            'right-0 origin-top-right' => $align === 'right',
            'left-0 origin-top-left' => $align === 'left',
        ])>
        {{ $menu }}
    </div>
</div>
