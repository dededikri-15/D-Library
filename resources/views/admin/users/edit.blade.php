@extends('layouts.app')

@section('title', __('users.edit_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('users.edit_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $user->email }}</p>

    <form method="POST" action="{{ route('users.update', $user) }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" :label="__('users.name')" required :value="$user->name" />
        <x-form.input name="email" :label="__('users.email')" type="email" required :value="$user->email" autocomplete="email" />

        <x-form.select name="gender" :label="__('users.gender')" required :allowEmpty="true"
                       :emptyLabel="__('users.choose_gender')" :options="App\Models\User::genderOptions()"
                       :value="$user->gender" />

        <x-form.input name="date_of_birth" :label="__('users.date_of_birth')" type="date"
                      :value="$user->date_of_birth?->format('Y-m-d')" max="{{ now()->format('Y-m-d') }}"
                      autocomplete="bday" />

        <x-form.select name="role" :label="__('users.role')" required :allowEmpty="false"
                       :options="collect(App\Models\User::roles())
                            ->mapWithKeys(fn ($role) => [$role => __('roles.'.$role)])
                            ->all()"
                       :value="$user->role" />

        <div class="rounded-lg border border-secondary/20 p-4">
            <p class="text-sm font-medium text-primary">{{ __('users.change_password') }}</p>
            <p class="mt-1 text-xs text-secondary">{{ __('users.leave_password_blank') }}</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-form.input name="password" :label="__('users.password')" type="password" autocomplete="new-password" />
                <x-form.input name="password_confirmation" :label="__('users.confirm_password')" type="password"
                              autocomplete="new-password" />
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('users.save_changes') }}</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">{{ __('users.cancel') }}</a>
        </div>
    </form>

    {{--
        Kartu perpustakaan — khusus anggota.

        Terpisah dari form edit di atas karena punya tombol aksi sendiri
        (perpanjang) dengan aturan bisnis yang tidak ada hubungannya dengan
        data profil. Pustakawan membaca checklist ini dulu; tombol hanya
        aktif kalau SEMUA syarat terpenuhi (syarat yang sama dihitung ulang
        di server saat route di-POST, jadi menekan tombol tersembunyi pun
        tetap ditolak).
    --}}
    @if ($user->isMember())
        @php
            // Kunci blocker → label. Ditulis di sini supaya urutan tampil
            // selalu sama, apa pun urutan pemeriksaan di model.
            $requirements = [
                'active_loans' => __('users.requirement_all_returned'),
                'unpaid_fine' => __('users.requirement_no_unpaid_fine'),
                'expired_too_long' => __('users.requirement_within_grace', [
                    'days' => App\Models\LibraryCard::renewalGraceDays(),
                ]),
            ];
            $blockers = $card?->renewalBlockers() ?? [];
            $canRenew = $card !== null && $card->canRenew();
        @endphp

        <div class="card mt-6 max-w-xl p-6">
            <p class="text-sm font-semibold text-primary">{{ __('users.library_card_section') }}</p>
            <p class="mt-1 text-xs text-secondary">{{ __('users.library_card_description') }}</p>

            @if ($card)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-secondary/20 px-4 py-3">
                    <div>
                        <p class="text-[0.7rem] tracking-wide text-secondary uppercase">{{ __('users.library_card_valid_until') }}</p>
                        <p class="mt-0.5 text-sm font-semibold tabular-nums text-primary">{{ $card->valid_until->translatedFormat('d M Y') }}</p>
                    </div>

                    @if ($card->isValid())
                        <span class="badge badge-available">{{ __('users.library_card_status_active') }}</span>
                    @else
                        <span class="badge badge-overdue">{{ __('users.library_card_status_expired') }}</span>
                    @endif
                </div>

                <p class="mt-5 text-label font-medium tracking-wide text-secondary uppercase">{{ __('users.renewal_requirements') }}</p>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($requirements as $key => $label)
                        @php($met = ! array_key_exists($key, $blockers))
                        <li class="flex items-start gap-2 text-sm {{ $met ? 'text-available' : 'text-overdue' }}">
                            @if ($met)
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                </svg>
                            @else
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            @endif
                            <span>{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($canRenew)
                    <form method="POST" action="{{ route('users.card.renew', $user) }}" class="mt-5" data-submit-once>
                        @csrf
                        <button type="submit" class="btn btn-primary">{{ __('users.renew_card') }}</button>
                    </form>
                @else
                    <button type="button" class="btn btn-primary mt-5" disabled aria-disabled="true">
                        {{ __('users.renew_card') }}
                    </button>
                    <p class="mt-2 text-xs text-secondary">{{ __('users.renew_blocked_hint') }}</p>
                @endif
            @else
                <p class="mt-4 text-sm text-secondary">{{ __('users.card_without_birthdate') }}</p>
            @endif
        </div>
    @endif
@endsection
