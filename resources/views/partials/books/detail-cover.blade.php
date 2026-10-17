        {{-- Cover --}}
        <div class="lg:col-span-4 xl:col-span-3">
            <div class="lg:sticky lg:top-28">
                <x-book-cover :book="$book" size="lg"
                              class="shadow-lift ring-1 ring-hairline" />

                @if ($book->hasFile())
                    <p class="mt-3 flex items-center justify-center gap-1.5 text-label text-secondary">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 14.25v-2.63c0-1.14-.46-2.23-1.28-3.03l-2.4-2.4a4.28 4.28 0 0 0-3.04-1.26H7.5m10.5 9.04v2.63c0 1.14-.46 2.23-1.28 3.03l-2.4 2.4a4.28 4.28 0 0 1-3.04 1.26H7.5m10.5-9.04H4.5a1.5 1.5 0 0 1-1.5-1.5V6.75a1.5 1.5 0 0 1 1.5-1.5h3.09a1.5 1.5 0 0 0 1.06-.44l1.69-1.7a1.5 1.5 0 0 1 1.06-.44h3.6a1.5 1.5 0 0 1 1.5 1.5v3.09a1.5 1.5 0 0 0 .44 1.06l1.7 1.69a1.5 1.5 0 0 0 1.06.44h1.5a1.5 1.5 0 0 1 1.5 1.5Z"/>
                        </svg>
                        {{ __('book.digital_available') }}
                    </p>
                @endif
            </div>
        </div>
