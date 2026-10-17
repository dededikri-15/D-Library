    {{-- Buku terkait --}}
    @if ($related->isNotEmpty())
        <section class="mt-16">
            <x-section-heading eyebrow="Rekomendasi" title="Buku Lainnya" />

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($related as $item)
                    <x-book-card :book="$item" />
                @endforeach
            </div>
        </section>
    @endif
