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
        <p class="mt-1 text-sm text-secondary">{{ $description ?? 'Kelola data ' . strtolower($title) . '.' }}</p>
    </div>

    @if ($canManage)
        <a href="{{ route($routeBase.'.create') }}" class="btn btn-primary">Tambah {{ strtolower($title) }}</a>
    @endif
</div>

<form method="GET" action="{{ $searchAction }}" class="mt-6 flex max-w-sm gap-2">
    <label for="q" class="sr-only">Cari {{ strtolower($title) }}</label>
    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari {{ strtolower($title) }}..."
           class="field-input mt-0">
    <button type="submit" class="btn btn-secondary shrink-0">Cari</button>
</form>

<div class="table-wrap mt-6">
    <table class="table">
        <thead>
            <tr>
                <th>Nama</th>
                <th class="w-28 text-right">Jumlah buku</th>
                @if ($canManage)
                    {{--
                        Edit dan Hapus dipisah jadi dua kolom, bukan digabung
                        di satu kolom "Aksi". Alasannya tombolnya beda sifat:
                        Edit reversible, Hapus permanen. Kalau berdempetan,
                        orang bisa salah klik yang salah — dan yang salah klik
                        biasanya Hapus.
                    --}}
                    <th class="w-24 text-right">Edit</th>
                    <th class="w-40 text-right">Hapus</th>
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
                            <a href="{{ route($routeBase.'.edit', $row) }}" class="btn btn-ghost btn-sm">Edit</a>
                        </td>
                        <td class="text-right">
                            {{--
                                Kalimat konfirmasi tidak boleh menjanjikan
                                "buku ikut terhapus": `books` memakai
                                cascadeOnDelete, jadi menekan Hapus pada
                                baris yang masih dipakai bisa menghapus
                                puluhan buku beserta riwayat pinjamannya.
                                Server menolak aksi itu (lihat
                                `Controller::destroyMasterData()`), dan
                                tombolnya sudah dimatikan di sini supaya
                                orang tidak/heran kenapa tidak terjadi apa-apa.
                            --}}
                            <form method="POST" action="{{ route($routeBase.'.destroy', $row) }}"
                                  data-confirm="Hapus {{ strtolower($title) }} &quot;{{ $row->name }}&quot;? Tindakan ini tidak bisa dibatalkan."
                                  data-confirm-title="Hapus {{ strtolower($title) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm"
                                        @disabled($row->books_count > 0)
                                        title="{{ $row->books_count > 0 ? 'Masih dipakai ' . $row->books_count . ' buku' : null }}">Hapus</button>
                            </form>
                            {{--
                                Alasan kenapa tombolnya mati ditulis di bawah
                                tombol, bukan cuma di atribut `title`. `title`
                                cuma muncul saat kursor diarahkan ke elemen itu —
                                di layar sentuh tidak pernah muncul sama sekali,
                                jadi tanpa teks ini orang mengira tombolnya rusak.
                            --}}
                            @if ($row->books_count > 0)
                                <p class="mt-1 text-xs text-secondary">Dipakai {{ $row->books_count }} buku</p>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $canManage ? 4 : 2 }}" class="p-0">
                        <x-empty-state class="border-0"
                                       title="Belum ada {{ strtolower($title) }}"
                                       description="{{ $canManage ? 'Klik "Tambah ' . strtolower($title) . '" untuk menambahkan data pertama.' : 'Data belum tersedia.' }}" />
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="mt-4">{{ $rows->links() }}</div>
@endif
