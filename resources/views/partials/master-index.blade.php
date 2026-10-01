@php
    $canManage = auth()->check() && auth()->user()->isStaff();
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
                    <td>
                        <span class="font-medium text-primary">{{ $row->name }}</span>
                        @if (! empty($row->{$subtitleField}))
                            <p class="mt-0.5 text-secondary">{{ Str::limit($row->{$subtitleField}, 80) }}</p>
                        @endif
                    </td>
                    <td class="text-right text-secondary">{{ $row->books_count }}</td>
                    @if ($canManage)
                        <td class="text-right">
                            <a href="{{ route($routeBase.'.edit', $row) }}" class="btn btn-ghost btn-sm">{{ __('master.edit') }}</a>
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
                    <td colspan="{{ $canManage ? 4 : 2 }}" class="p-0">
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
