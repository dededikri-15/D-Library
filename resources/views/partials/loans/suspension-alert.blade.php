    {{-- Masa sanksi larangan meminjam. Tampil di halaman ini karena
         riwayat peminjaman adalah halaman pertama yang dibuka anggota
         setelah bukunya dikembalikan — pesan "belum boleh meminjam" harus
         terlihat di sana, bukan disembunyikan di halaman katalog yang
         justru menggoda dia untuk menekan tombol Pinjam. --}}
    @if (auth()->user()?->isSuspended())
        <x-alert variant="warning" class="mt-6" :dismissible="false"
                 :title="__('loans.suspension_title')">
            {{ __('loans.suspension_body', [
                'hours' => (int) config('perpustakaan.loan.suspension_hours'),
                'until' => auth()->user()->suspensionEndsAt()
                    ?->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'))
                    ->translatedFormat('d M Y H:i'),
            ]) }}
        </x-alert>
    @endif
