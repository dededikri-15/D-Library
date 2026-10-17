    {{--
        Filter `active=1` datang dari kartu "Peminjaman Aktif" di dasbor, jadi
        filter status biasa tidak bisa menampilkannya: "aktif" itu status
        "dipinjam" + "terlambat" sekaligus, bukan satu status. Karena itu tidak
        ada checkbox untuk mengaktifkannya — kalau ada, orang akan mengira
        "aktif" itu status keempat di dropdown ini.

        Filter itu muncul sebagai catatan kecil di bawah form, yang sekaligus
        satu-satunya cara membuangnya lagi. Tanpa catatan itu, orang yang
        menekan "Terapkan" justru ikut menghapus filternya, karena form-nya
        tidak pernah mengirim `active`.
    --}}
    <form method="GET" class="mt-6 flex max-w-xs items-end gap-2">
        <div class="flex-1">
            <label for="status" class="field-label">{{ __('loans.filter_status') }}</label>
            <select id="status" name="status" class="field-input">
                <option value="">{{ __('loans.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ App\Models\Loan::statusOptions()[$status] ?? $status }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary shrink-0">{{ __('loans.apply') }}</button>
    </form>

    @if ($onlyActive)
        <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-secondary">
            <span class="badge badge-borrowed">{{ __('loans.active') }}</span>
            {{ __('loans.active_description') }}
            <a href="{{ route('loans.index') }}" class="link-accent">{{ __('loans.show_all') }}</a>
        </p>
    @endif
