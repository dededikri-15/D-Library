@php
    $timezone = (string) config('perpustakaan.display_timezone', 'Asia/Jakarta');
    $now = now($timezone)->locale(app()->getLocale());
@endphp

<div data-public-stats-widget
     class="order-last flex w-full min-w-0 items-center justify-between gap-3 text-xs sm:text-sm lg:order-0 lg:ml-auto lg:w-auto">
    @auth
        <div data-system-indicator data-health-url="{{ url('/up') }}" data-state="checking"
            data-online-label="{{ __('ui.system_active') }}" data-offline-label="{{ __('ui.system_offline') }}"
            class="inline-flex shrink-0 items-center gap-2 font-medium text-secondary" role="status" aria-live="polite">
            <span class="relative grid h-3 w-3 shrink-0 place-items-center" aria-hidden="true">
                <span data-status-ping hidden
                    class="absolute h-3 w-3 animate-ping rounded-full bg-available/70 [animation-duration:3s] motion-reduce:animate-none"></span>
                <span data-status-dot class="relative h-2.5 w-2.5 rounded-full bg-secondary"></span>
            </span>
            <span data-status-label>{{ __('ui.system_checking') }}</span>
        </div>
    @endauth

    <form method="POST" action="{{ route('locale.update') }}" class="shrink-0">
        @csrf
        <button type="submit" name="locale" value="{{ app()->getLocale() === 'id' ? 'en' : 'id' }}"
            class="inline-flex h-8 min-w-9 cursor-pointer items-center justify-center rounded-md px-2 text-xs font-semibold text-secondary transition-colors hover:bg-secondary/10 hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tertiary"
            aria-label="{{ app()->getLocale() === 'id' ? __('navigation.switch_to_english') : __('navigation.switch_to_indonesian') }}"
            title="{{ app()->getLocale() === 'id' ? __('navigation.switch_to_english') : __('navigation.switch_to_indonesian') }}">
            {{ app()->getLocale() === 'id' ? 'EN' : 'ID' }}
        </button>
    </form>

    <time data-live-clock data-timezone="{{ $timezone }}" data-locale="{{ app()->getLocale() }}"
          datetime="{{ $now->toIso8601String() }}" class="min-w-0 text-right tabular-nums text-secondary">
        <span data-clock-date>{{ $now->translatedFormat('l, d F Y') }}</span>
        <span aria-hidden="true" class="mx-1">·</span>
        <span data-clock-time class="whitespace-nowrap">{{ $now->format('H:i:s') }}</span>
    </time>
</div>
