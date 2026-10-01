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
                <div data-avatar-preview
                     class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-full border border-hairline bg-tertiary/10 text-2xl font-semibold text-tertiary">
                    <x-avatar :user="$user" size="lg" alt="" class="h-full w-full border-0" />
                </div>

                <h2 class="mt-4 text-lg font-semibold text-primary">{{ $user->name }}</h2>
                <p class="mt-1 break-all text-sm text-secondary">{{ $user->email }}</p>

                <span class="badge badge-muted mt-3">{{ $user->roleLabel() }}</span>
            </div>

            <dl class="mt-6 space-y-3 border-t border-hairline pt-5 text-sm">
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

                {{-- Foto profil. Tidak wajib: kosong berarti pakai avatar huruf. --}}
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
                        Kontrol "hapus foto" hanya muncul kalau memang ada foto.
                        Kalau tidak ada, checkbox-nya tidak perlu dihiraukan —
                        dan menampilkan kotak kosong membuat user mengira ada
                        sesuatu yang perlu dibersihkan.
                    --}}
                    @if ($user->avatar)
                        <label class="mt-3 flex items-center gap-2 text-sm text-secondary">
                            <input type="checkbox" name="remove_avatar" value="1"
                                   class="rounded-sm border-secondary/30 text-overdue focus:ring-overdue/30">
                            {{ __('profile.remove_photo') }}
                        </label>
                    @endif
                </div>

                <x-form.input name="name" :label="__('profile.name')" required autocomplete="name"
                              :value="$user->name" />
                <x-form.input name="email" :label="__('profile.email')" type="email" required
                              autocomplete="email" :value="$user->email" />

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