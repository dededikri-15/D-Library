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
                    <dt class="text-secondary">{{ __('profile.date_of_birth') }}</dt>
                    <dd class="text-right font-medium text-primary">
                        {{ $user->date_of_birth?->translatedFormat('d M Y') ?? __('users.gender_not_set') }}
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
