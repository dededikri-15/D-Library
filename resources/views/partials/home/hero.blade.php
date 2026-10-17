    {{-- Hero --}}
    <section class="bg-hero relative overflow-hidden rounded-xl border border-hairline">
        <span class="pointer-events-none absolute -top-24 -right-16 h-72 w-72 rounded-full blur-3xl" style="background-color: var(--hero-glow)" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-32 -left-20 h-72 w-72 rounded-full blur-3xl" style="background-color: var(--hero-glow)" aria-hidden="true"></span>

        <div class="relative px-6 py-16 sm:px-10 sm:py-20 lg:px-16 lg:py-24">
            <p class="section-eyebrow animate-fade-in">D-Library</p>

            <h1 class="mt-5 max-w-3xl text-display font-extrabold tracking-tight text-balance text-primary animate-fade-up">
                {{ __('home.headline') }} <span class="text-tertiary">{{ __('home.headline_accent') }}</span>
            </h1>

            <p class="mt-5 max-w-xl text-lg leading-relaxed text-secondary animate-fade-up [animation-delay:80ms]">
                {{ __('home.description') }}
            </p>

            <div class="mt-9 flex flex-wrap items-center gap-3 animate-fade-up [animation-delay:160ms]">
                @auth
                    <a href="{{ route('books.index') }}" class="btn btn-secondary btn-lg">{{ __('home.explore_catalog') }}</a>
                @else
                    <a href="{{ route('books.index') }}" class="btn btn-blue-primary btn-lg">
                        {{ __('home.explore_catalog') }}
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-blue-outline btn-lg">{{ __('home.sign_in') }}</a>
                    @if (config('perpustakaan.registration.enabled', true))
                        <a href="{{ route('register') }}" class="btn btn-blue-outline btn-lg">{{ __('home.register') }}</a>
                    @endif
                @endauth
            </div>
        </div>
    </section>
