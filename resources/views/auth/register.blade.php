@extends('layouts.app')

@section('title', __('auth.register').' - '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <div class="card p-8">
            <h1 class="text-h1 font-semibold text-primary">{{ __('auth.register_title') }}</h1>
            <p class="mt-2 text-sm text-secondary">{{ __('auth.register_description') }}</p>

            {{--
    `enctype="multipart/form-data"` wajib karena ada input foto profil. Tanpa
    atribut ini, browser tetap mengirim form tapi field `avatar` hilang sebelum
    sampai ke PHP — hasilnya user mengira gagal mengunggah padahal berkasnya
    memang tidak pernah terkirim, dan tidak ada error yang muncul karena
    validasinya memang tidak pernah jalan.
--}}
<form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-8 space-y-5" data-submit-once>
                @csrf

                <x-form.input name="name" :label="__('auth.full_name')" required autocomplete="name" />
                <x-form.input name="email" :label="__('auth.email')" type="email" required autocomplete="username" />
                <x-form.input name="password" :label="__('auth.password')" type="password" required
                              autocomplete="new-password" :hint="__('auth.password_hint')" />
                <x-form.input name="password_confirmation" :label="__('auth.confirm_password')" type="password" required
                              autocomplete="new-password" />

                {{--
                    Foto profil: OPSIONAL. Label-nya menyatakan itu dengan jelas,
                    karena "tidak wajib" yang tidak ditulis orang tetap dibaca
                    sebagai wajib oleh sebagian pengguna. Kalau dikosongkan,
                    avatar huruf (inisial nama) yang dipakai.
                --}}
                <div>
                    <label for="avatar" class="field-label">{{ __('auth.photo_profile') }}</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*"
                           class="field-input file:mr-3 file:rounded-sm file:border-0 file:bg-tertiary/10 file:px-3 file:py-1.5 file:text-label file:font-medium file:text-tertiary
                                  @class(['border-overdue' => $errors->has('avatar')])>

                    <p class="mt-1 text-label text-secondary">{{ __('auth.photo_profile_hint') }}</p>

                    @error('avatar')
                        <p class="mt-1 text-label text-overdue">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">{{ __('auth.register') }}</button>
            </form>

            <p class="mt-6 text-center text-sm text-secondary">
                {{ __('auth.has_account') }}
                <a href="{{ route('login') }}" class="font-medium text-tertiary hover:underline">{{ __('auth.login') }}</a>
            </p>
        </div>
    </div>
@endsection
