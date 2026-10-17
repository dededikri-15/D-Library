@extends('layouts.app')

@section('title', __('navigation.library_card').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('navigation.library_card') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('member.library_card_subtitle') }}</p>

    @if (! $card)
        {{--
            Tanggal lahir belum diisi — nomor kartu (DDMMYY) tidak bisa
            dibuat. Tampilkan pesan + link ke edit profil alih-alih
            merender kartu kosong.
        --}}
        <div class="card mt-6 max-w-xl p-6">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-overdue" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-primary">{{ __('member.complete_profile_first') }}</p>
                    <p class="mt-1 text-sm text-secondary">{{ __('member.complete_profile_hint') }}</p>
                    <a href="{{ route('profile.show') }}" class="btn btn-primary btn-sm mt-4">
                        {{ __('member.go_to_profile') }}
                    </a>
                </div>
            </div>
        </div>
    @else

    @include('partials.library-cards.card-panel', ['card' => $card])
    @endif
@endsection
