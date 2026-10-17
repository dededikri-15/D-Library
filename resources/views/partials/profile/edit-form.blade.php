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
                <x-form.input name="date_of_birth" :label="__('profile.date_of_birth')" type="date"
                              :value="$user->date_of_birth?->format('Y-m-d')" max="{{ now()->format('Y-m-d') }}"
                              autocomplete="bday" />

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
