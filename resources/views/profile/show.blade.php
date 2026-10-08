@extends('layouts.app')

@section('title', __('profile.title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('profile.title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('profile.description') }}</p>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        {{--
            Kartu identitas. Isinya HANYA data yang sudah ada di `users` dan
            angka ringkas dari `loadCount()` di ProfileController. Tidak ada satu
            pun field yang bisa diedit di sini — semua isian ada di form di
            sebelah kanan, jadi tidak ada dua tempat yang bisa menampilkan data
            yang sama lalu berbeda.
        --}}
        <section class="card p-6 lg:col-span-1">
            <div class="flex flex-col items-center text-center">
                {{--
                    `data-avatar-preview` dibaca `initAvatarPreview()` di
                    resources/js/app.js: begitu user memilih berkas, lingkaran ini
                    langsung menampilkan berkas itu supaya dia tahu foto yang
                    sedang dipilih tanpa harus menyimpan dulu. Kalau JS mati,
                    form tetap berfungsi penuh — hanya pratinjaunya yang hilang.
                --}}
                {{--
                    Border-nya HANYA dari komponen `x-avatar`, tidak dari
                    wrapper ini. `border-0` yang dulu ditulis di sini tidak
                    pernah menang: di CSS hasil build, aturan `.border-0`
                    ditulis sebelum `.border`, sehingga tetap kalah cascade
                    dan muncul cincin ganda (border wrapper + border
                    komponen) yang terlihat tidak rapi.
                --}}
                <div data-avatar-preview
                     class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-full bg-tertiary/10 text-2xl font-semibold text-tertiary">
                    <x-avatar :user="$user" size="lg" alt="" class="h-full w-full" />
                </div>

                {{--
                    Hapus foto: tombol tersendiri dengan dialog konfirmasi,
                    bukan checkbox yang harus disimpan lewat "Simpan Perubahan".
                    Formnya DI LUAR form update di sebelah kanan — form dalam
                    form tidak valid di HTML dan browser akan mengabaikannya.

                    Hanya muncul kalau memang ada foto: kalau tidak ada, tidak
                    ada yang bisa dihapus, dan tombol merah hanya membingungkan.
                    Setelah dihapus, avatar kembali mengikuti jenis kelamin.
                --}}
                @if ($user->avatar)
                    <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mt-3 w-full"
                          data-confirm="{{ __('profile.remove_photo_confirm') }}" data-submit-once>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm w-full">
                            {{ __('profile.remove_photo') }}
                        </button>
                    </form>
                @endif

                <h2 class="mt-4 text-lg font-semibold text-primary">{{ $user->name }}</h2>
                <p class="mt-1 break-all text-sm text-secondary">{{ $user->email }}</p>

                <span class="badge badge-muted mt-3">{{ $user->roleLabel() }}</span>
            </div>

            <dl class="mt-6 space-y-3 border-t border-hairline pt-5 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-secondary">{{ __('profile.gender') }}</dt>
                    <dd class="text-right font-medium text-primary">
                        {{ $user->genderLabel() ?? __('users.gender_not_set') }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-secondary">{{ __('profile.member_since') }}</dt>
                    <dd class="text-right font-medium text-primary">
                        {{ $user->created_at?->translatedFormat('d M Y') }}
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-secondary">{{ __('profile.total_loans') }}</dt>
                    <dd class="font-medium tabular-nums text-primary">{{ $user->loans_count }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-secondary">{{ __('profile.active_loans') }}</dt>
                    <dd class="font-medium tabular-nums text-primary">{{ $user->active_loans_count }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-secondary">{{ __('profile.favorites') }}</dt>
                    <dd class="font-medium tabular-nums text-primary">{{ $user->favorites_count }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-secondary">{{ __('profile.reading_history') }}</dt>
                    <dd class="font-medium tabular-nums text-primary">{{ $user->reading_histories_count }}</dd>
                </div>
            </dl>
        </section>

        <section class="card p-6 lg:col-span-2">
            <h2 class="text-lg font-semibold text-primary">{{ __('profile.edit_title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ __('profile.edit_description') }}</p>

            {{--
                `enctype="multipart/form-data"` WAJIB karena ada input file.
                Tanpa itu, browser tetap mengirim form, tapi field `avatar`
                hilang sebelum sampai ke PHP — berkas yang dipilih tidak akan pernah
                sampai ke server, dan tidak ada error yang muncul karena
                validasinya memang tidak pernah berjalan.
            --}}
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data"
                  data-submit-once class="mt-6 space-y-5">
                @csrf
                @method('PATCH')

                {{-- Foto profil opsional; fallback avatar mengikuti jenis kelamin. --}}
                <div>
                    <label for="avatar" class="field-label">{{ __('profile.photo') }}</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" data-avatar-input
                           class="field-input file:mr-3 file:rounded-sm file:border-0 file:bg-tertiary/10 file:px-3 file:py-1.5 file:text-label file:font-medium file:text-tertiary
                                  {{-- `data-avatar-input` ada pada input yang
                                       sama supaya JS bisa mencocokkan
                                       preview-nya. --}}
                           @class(['border-overdue' => $errors->has('avatar')])>

                    <p class="mt-1 text-label text-secondary">{{ __('profile.photo_hint') }}</p>

                    @error('avatar')
                        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
                    @enderror

                    {{--
                        Penghapusan foto tidak lewat checkbox di form ini lagi:
                        ada tombol "Hapus foto profil" khusus di kartu sebelah
                        kiri (route `profile.avatar.destroy`), satu klik dengan
                        dialog konfirmasi. `remove_avatar` tetap ditangani di
                        server untuk kompatibilitas lama.
                    --}}
                </div>

                <x-form.input name="name" :label="__('profile.name')" required autocomplete="name"
                              :value="$user->name" />
                <x-form.input name="email" :label="__('profile.email')" type="email" required
                              autocomplete="email" :value="$user->email" />
                <x-form.select name="gender" :label="__('profile.gender')" required :allowEmpty="true"
                               :emptyLabel="__('users.choose_gender')" :options="App\Models\User::genderOptions()"
                               :value="$user->gender" />

                {{--
                    Ganti kata sandi dipisah jadi blok tersendiri supaya jelas
                    opsional: kosongkan kedua kolom berarti "jangan diubah".
                    Sama seperti di form edit pengguna milik pustakawan.
                --}}
                <div class="rounded-lg border border-secondary/20 p-4">
                    <p class="text-sm font-medium text-primary">{{ __('profile.change_password') }}</p>
                    <p class="mt-1 text-xs text-secondary">{{ __('profile.leave_password_blank') }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-form.input name="password" :label="__('profile.password')" type="password"
                                      autocomplete="new-password" />
                        <x-form.input name="password_confirmation" :label="__('profile.confirm_password')"
                                      type="password" autocomplete="new-password" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-primary">{{ __('profile.save') }}</button>
                    <a href="{{ route($user->isStaff() ? 'dashboard' : 'anggota.dashboard') }}"
                       class="btn btn-secondary">{{ __('profile.back') }}</a>
                </div>
            </form>
        </section>
    </div>
@endsection
