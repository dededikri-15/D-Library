@extends('layouts.app')

@section('title', __('card_requests.title').' - '.config('app.name'))

@section('content')
    @php($months = App\Models\LibraryCard::renewalMonths())

    <h1 class="text-h1 font-semibold text-primary">{{ __('card_requests.title') }}</h1>
    <p class="mt-1 text-sm text-secondary">
        {{ __('card_requests.description', ['months' => $months]) }}
    </p>

    {{--
        Daftar pengajuan perpanjangan yang menunggu konfirmasi pustakawan.

        Pengajuan muncul di sini setelah anggota menekan "Ajukan perpanjangan"
        di halaman kartunya; kolom "Syarat" dihitung ULANG tiap baris lewat
        `renewalBlockers()` — syarat bisa berubah setelah pengajuan dikirim
        (mis. anggota meminjam buku lagi), jadi keputusan akhir tetap di
        server saat tombol konfirmasi ditekan.
    --}}
    <div class="table-wrap mt-6">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('card_requests.member') }}</th>
                    <th>{{ __('card_requests.card_number') }}</th>
                    <th>{{ __('card_requests.requested_at') }}</th>
                    <th>{{ __('card_requests.valid_until') }}</th>
                    <th>{{ __('card_requests.requirements') }}</th>
                    <th class="text-right">{{ __('card_requests.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cards as $card)
                    @php($blockers = $card->renewalBlockers())
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <x-avatar :user="$card->user" size="sm" alt="" />
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-primary">{{ $card->user?->name ?? '-' }}</p>
                                    <p class="truncate text-label text-secondary">{{ $card->user?->email ?? '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="font-mono text-secondary">{{ $card->card_number }}</td>
                        <td class="text-secondary">
                            {{ $card->renewal_requested_at?->translatedFormat('d M Y') ?? '-' }}
                        </td>
                        <td class="text-secondary">{{ $card->valid_until->translatedFormat('d M Y') }}</td>
                        <td>
                            @if ($blockers === [])
                                <span class="badge badge-available whitespace-nowrap">{{ __('card_requests.requirements_met') }}</span>
                            @else
                                <ul class="space-y-1">
                                    @foreach ([
                                        'active_loans' => __('users.requirement_all_returned'),
                                        'suspended' => __('users.requirement_not_suspended'),
                                        'expired_too_long' => __('users.requirement_within_grace', [
                                            'days' => App\Models\LibraryCard::renewalGraceDays(),
                                        ]),
                                    ] as $key => $label)
                                        @if (array_key_exists($key, $blockers))
                                            <li class="flex items-start gap-1.5 text-sm text-overdue">
                                                <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24"
                                                     stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                </svg>
                                                <span>{{ $label }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            {{--
                                Tombol sengaja `whitespace-nowrap`: kolom aksi
                                sempit di tabel membuat teks tombol patah jadi
                                dua baris ("Konfirmasi (+3" / "bulan)") yang
                                terlihat rusak. Kalau layar terlalu sempit,
                                `table-wrap` (overflow-x-auto) menggeser tabel
                                — bukan memecah isi tombol.
                            --}}
                            <div class="flex flex-wrap items-center justify-end gap-2 whitespace-nowrap">
                                @if ($blockers === [])
                                    <form method="POST"
                                          action="{{ route('library-cards.renew-confirm', $card->user) }}"
                                          data-submit-once
                                          data-confirm="{{ __('card_requests.confirm_confirmation', ['name' => $card->user?->name ?? '-', 'months' => $months]) }}"
                                          data-confirm-title="{{ __('card_requests.confirm_title') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm whitespace-nowrap">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                 stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                            {{ __('card_requests.confirm', ['months' => $months]) }}
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-primary btn-sm whitespace-nowrap"
                                            disabled aria-disabled="true">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                             stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                        </svg>
                                        {{ __('card_requests.confirm', ['months' => $months]) }}
                                    </button>
                                @endif

                                {{-- btn-secondary (punya border), bukan ghost:
                                     ghost nyaris tak terlihat di latar tabel
                                     sehingga "Edit pengguna" terbaca sebagai
             teks biasa, bukan tautan. --}}
                                <a href="{{ route('users.edit', $card->user) }}"
                                   class="btn btn-secondary btn-sm whitespace-nowrap">
                                    {{ __('card_requests.view_user') }}
                                </a>
                            </div>
                            @if ($blockers !== [])
                                <p class="mt-1 text-right text-xs text-secondary">{{ __('card_requests.blocked_hint') }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <x-empty-state class="border-0"
                                           :title="__('card_requests.empty')"
                                           :description="__('card_requests.empty_description')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $cards->links() }}</div>
@endsection
