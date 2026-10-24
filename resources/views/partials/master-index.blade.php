@php
    $canManage = auth()->check() && auth()->user()->isStaff();
    $photoField = $photoField ?? null;
    $colspan = ($canManage ? 4 : 2) + ($photoField ? 1 : 0);
@endphp

{{--
    Layout daftar untuk master data (kategori, penulis, penerbit). Semuanya
    punya bentuk yang sama: judul, cari, tabel, aksi. Yang berbeda hanya
    nama route-nya dan nama kolom ringkasan di bawah judul, karena tiap
    tabel menyimpannya dengan nama berbeda: kategori memakai description,
    penulis memakai biography, penerbit memakai address. Karena itu
    subtitleField wajib diisi pemanggil.
--}}

<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-h1 font-semibold text-primary">{{ $title }}</h1>
        <p class="mt-1 text-sm text-secondary">{{ $description ?? __('master.manage', ['entity' => strtolower($title)]) }}</p>
    </div>

    @if ($canManage)
        <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary">{{ __('master.add_entity', ['entity' => strtolower($title)]) }}</a>
    @endif
</div>

<form method="GET" action="{{ $searchAction }}" class="mt-6 flex max-w-sm gap-2">
    <label for="q" class="sr-only">{{ __('master.search_entity', ['entity' => strtolower($title)]) }}</label>
    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('master.search_entity', ['entity' => strtolower($title)]) }}..."
           class="field-input mt-0">
    <button type="submit" class="btn btn-secondary shrink-0">{{ __('master.search') }}</button>
</form>

<div class="table-wrap mt-6">
    <table class="table">
        <thead>
            <tr>
                @if ($photoField)
                    <th class="w-20 text-center">{{ __('master.photo') }}</th>
                @endif
                <th>{{ __('master.name') }}</th>
                <th class="w-28 text-right">{{ __('master.books_count') }}</th>
                @if ($canManage)
                    {{--
                        Edit dan Hapus dipisah jadi dua kolom, bukan digabung
                        di satu kolom "Aksi". Alasannya tombolnya beda sifat:
                        Edit reversible, Hapus permanen. Kalau berdempetan,
                        orang bisa salah klik yang salah — dan yang salah klik
                        biasanya Hapus.
                    --}}
                    <th class="w-24 text-right">{{ __('master.edit') }}</th>
                    <th class="w-40 text-right">{{ __('master.delete') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @if ($photoField)
                        <td class="text-center">
                            @if ($row->{$photoField})
                                <img src="{{ Storage::url($row->{$photoField}) }}"
                                     alt="{{ $row->name }}"
                                     class="w-10 h-10 rounded-full object-cover border border-hairline">
                            @else
                                <div class="w-10 h-10 rounded-full bg-surface/50 border border-hairline flex items-center justify-center text-secondary text-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                            @endif
                        </td>
                    @endif
                    <td>
                        <span class="font-medium text-primary">{{ $row->name }}</span>
                        @if (! empty($row->{$subtitleField}))
                            <p class="mt-0.5 text-secondary">{{ Str::limit($row->{$subtitleField}, 80) }}</p>
                        @endif
                    </td>
                    <td class="text-right text-secondary">{{ $row->books_count }}</td>
                    @if ($canManage)
                        <td class="text-right">
                            {{-- btn-secondary (punya border), bukan ghost:
                                 ghost nyaris tak terlihat di latar tabel
                                 sehingga "Edit" terbaca sebagai teks biasa. --}}
                            <a href="{{ route($routeBase.'.edit', $row) }}" class="btn btn-secondary btn-sm">{{ __('master.edit') }}</a>
                        </td>
                        <td class="text-right">
                            {{--
                                Kalimat konfirmasi wajib menyebut berapa banyak
                                buku yang ikut terhapus. `books.category_id` (dan
                                dua kolom lain) memakai `cascadeOnDelete()`, jadi
                                menghapus satu kategori berarti menghapus isi dan
                                riwayat pinjamannya juga — irreversibel, dan
                                tidak akan muncul di mana pun setelah selesai.
                                Tombolnya sengaja tidak dimatikan: pustakawan
                                harus tetap bisa membereskan data yang salah
                                input, dan menahan hapus hanya karena masih ada
                                buku di dalamnya memaksa dia membuka tiap buku
                                satu per satu.

                                Tanda kutip ditulis apa adanya (`"`), bukan
                                `&quot;`. Nilai attribute ini sudah keluar dari
                                `{{ }}`, jadi `e()` yang meng-escape-nya; kalau
                                `&quot;` ikut ditulis di sini, yang tampil di
                                dialog adalah teks `&quot;` itu sendiri.
                            --}}
                            <form method="POST" action="{{ route($routeBase.'.destroy', $row) }}"
                                  data-confirm="{{ $row->books_count > 0
                                      ? __('master.delete_question_used', ['entity' => strtolower($title), 'name' => $row->name, 'count' => $row->books_count])
                                      : __('master.delete_question_unused', ['entity' => strtolower($title), 'name' => $row->name])
                                  }}"
                                  data-confirm-title="{{ __('master.delete_entity', ['entity' => strtolower($title)]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('master.delete') }}</button>
                            </form>
                            {{--
                                Jumlah buku yang ikut terhapus ditulis di bawah
                                tombol, bukan cuma di dalam dialog. Dialog cuma
                                muncul setelah tombol ditekan, padahal akibatnya
                                harus terbaca dari melirik tabel saja.
                            --}}
                            @if ($row->books_count > 0)
                                <p class="mt-1 text-xs text-overdue">{{ __('master.deleted_count', ['count' => $row->books_count]) }}</p>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colspan }}" class="p-0">
                        <x-empty-state class="border-0"
                                       :title="__('master.empty_entity', ['entity' => strtolower($title)])"
                                       :description="$canManage ? __('master.empty_add', ['entity' => strtolower($title)]) : __('master.empty')" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="mt-4">{{ $rows->links() }}</div>
@endif
