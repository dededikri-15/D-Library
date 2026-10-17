    {{-- Ringkasan singkat. Tiga angka ini jadi kartu, bukan teks, karena
         dari sanalah orang cepat tahu "berapa yang masih harus saya
         kembalikan". --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([['label' => __('loans.active_count'), 'value' => $activeCount, 'tone' => 'primary'], ['label' => __('loans.overdue_count'), 'value' => $overdueCount, 'tone' => $overdueCount > 0 ? 'overdue' : 'primary'], ['label' => __('loans.returned_count'), 'value' => $returnedCount, 'tone' => 'primary']] as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :tone="$stat['tone']" :icon="false" />
        @endforeach
    </div>
