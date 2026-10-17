                            {{--
                                Form favorit (Task 14.7). `data-ajax` membuat form ini
                                dikirim lewat fetch dan tidak me-reload halaman; tanpa
                                JS ia tetap form POST biasa yang me-redirect, jadi tidak
                                ada jalur yang hilang.

                                action DAN method ikut berganti lewat `data-store-url` /
                                `data-destroy-url`, karena form yang sama harus bisa
                                melakukan tambah DAN hapus. Kalau hanya label tombolnya
                                yang berubah, klik kedua akan mengirim POST ke endpoint
                                yang salah.

                                `data-ajax-fallback` mengizinkan JS mengirim ulang lewat
                                jalur native kalau koneksi putus sebelum server
                                menjawab. Aman di sini karena toggle ini idempoten:
                                tambah dua kali tetap satu baris, hapus yang tidak ada
                                tidak merusak apa pun. Form lain TIDAK boleh memakai
                                atribut ini tanpa ditinjau dulu, karena request ulang
                                bisa jadi dobel.
                            --}}
                            <form method="POST"
                                  data-ajax
                                  data-ajax-fallback
                                  data-favorite-toggle
                                  data-store-url="{{ route('favorites.store', $book) }}"
                                  data-destroy-url="{{ route('favorites.destroy', $book) }}"
                                  action="{{ $isFavorite ? route('favorites.destroy', $book) : route('favorites.store', $book) }}">
                                @csrf
                                @if ($isFavorite) @method('DELETE') @endif
                                {{-- Kedua label ditulis di Blade, bukan ditulis ulang
                                     di JS. Kalau kalimat "Tambah ke favorit" ada di
                                     dua tempat, sooner or later salah satu tidak
                                     ikut diubah dan tombolnya berbohong. --}}
                                <button type="submit" class="btn btn-ghost {{ $isFavorite ? 'text-overdue' : '' }}" data-toggle-button
                                        data-label-add="{{ __('book.add_favorite') }}"
                                        data-label-remove="{{ __('book.remove_favorite') }}"
                                        aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                                    <svg class="h-4 w-4" data-toggle-icon
                                         fill="{{ $isFavorite ? 'currentColor' : 'none' }}"
                                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                                    </svg>
                                    <span data-toggle-label>{{ $isFavorite ? __('book.remove_favorite') : __('book.add_favorite') }}</span>
                                </button>
                            </form>
