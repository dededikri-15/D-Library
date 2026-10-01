<footer class="mt-auto border-t border-hairline bg-surface/60">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-md bg-brand text-on-brand" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6.25S10.5 4.75 8 4.75c-1.1 0-2 .35-2.75.75v12.5c.75-.4 1.65-.75 2.75-.75 2.5 0 4 1.5 4 1.5s1.5-1.5 4-1.5c1.1 0 2 .35 2.75.75V5.5c-.75-.4-1.65-.75-2.75-.75-2.5 0-4 1.5-4 1.5Zm0 0V18.75" />
                        </svg>
                    </span>
                    <span class="text-sm font-bold tracking-tight text-primary">{{ config('app.name') }}</span>
                </a>

                <p class="mt-4 text-sm text-secondary">
                    {{ __('footer.description') }}
                </p>
            </div>

            <nav aria-label="{{ __('footer.explore') }}">
                <p class="text-label font-semibold tracking-wide text-primary uppercase">{{ __('footer.explore') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}"
                            class="text-secondary transition-colors hover:text-tertiary">{{ __('navigation.home') }}</a></li>
                    <li><a href="{{ route('books.index') }}"
                            class="text-secondary transition-colors hover:text-tertiary">{{ __('footer.catalog') }}</a></li>
                    <li><a href="{{ route('categories.public') }}"
                            class="text-secondary transition-colors hover:text-tertiary">{{ __('navigation.categories') }}</a></li>
                    @guest
                        <li><a href="{{ route('login') }}"
                                class="text-secondary transition-colors hover:text-tertiary">{{ __('navigation.login') }}</a></li>
                    @else
                        <li>
                            <a href="{{ route(auth()->user()->isStaff() ? 'dashboard' : 'anggota.dashboard') }}"
                                class="text-secondary transition-colors hover:text-tertiary">{{ __('footer.my_dashboard') }}</a>
                        </li>
                    @endguest
                </ul>
            </nav>

            <nav aria-label="{{ __('footer.account') }}">
                <p class="text-label font-semibold tracking-wide text-primary uppercase">{{ __('footer.account') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @guest
                        <li>
                            <a href="{{ route('login') }}"
                                class="text-secondary transition-colors hover:text-tertiary">{{ __('navigation.login') }}</a>
                        </li>
                        @if (config('perpustakaan.registration.enabled', true))
                            <li>
                                <a href="{{ route('register') }}"
                                    class="text-secondary transition-colors hover:text-tertiary">{{ __('footer.join_member') }}</a>
                            </li>
                        @endif
                    @else
                        @if (auth()->user()->isMember())
                            <li>
                                <a href="{{ route('favorites.index') }}"
                                    class="text-secondary transition-colors hover:text-tertiary">{{ __('navigation.favorites') }}</a>
                            </li>
                            <li>
                                <a href="{{ route('reading-histories.index') }}"
                                    class="text-secondary transition-colors hover:text-tertiary">{{ __('navigation.reading_history') }}</a>
                            </li>
                        @else
                            <li><span class="text-secondary">{{ __('footer.signed_in_as', ['role' => auth()->user()->roleLabel()]) }}</span></li>
                        @endif
                    @endguest
                </ul>
            </nav>

            <div>
                <p class="text-label font-semibold tracking-wide text-primary uppercase">{{ __('footer.service_hours') }}</p>
                <ul class="mt-4 space-y-2.5 text-sm text-secondary">
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        {{ __('footer.weekdays') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <svg class="h-4 w-4 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                        {{ __('footer.location') }}
                    </li>
                </ul>
            </div>
        </div>

        {{-- Baris paling bawah. `built_with` pernah duduk di pojok kanan (sm:justify-between)
             dan tertutup tombol "Kembali ke atas" yang fixed di kanan bawah. Sekarang
             ditumpuk di tengah, jadi tidak pernah bertabrakan dengan tombol itu. --}}
        <div
            class="mt-10 flex flex-col items-center gap-1 border-t border-hairline pt-6 text-center text-sm text-secondary">
            <p>&copy; {{ now()->year }} {{ config('app.name') }}</p>
            <p class="text-label">{{ __('footer.built_with', ['version' => Illuminate\Foundation\Application::VERSION]) }}</p>
        </div>
    </div>
</footer>
