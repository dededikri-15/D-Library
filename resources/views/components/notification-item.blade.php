@props([
    'notification',
    'compact' => false,
])

{{--
    Satu baris notifikasi — dipakai oleh lonceng di topbar (compact) dan
    halaman riwayat (lebar).

    Teks TIDAK dibaca dari payload. `title` dan `body` dirender di sini dari
    `lang/{locale}/notifications.php` berdasarkan `data.type`, dengan
    parameter mentah dari `data` sebagai pengganti placeholder. Konsekuensinya
    notifikasi yang masuk saat user berbahasa Indonesia tetap terbaca dalam
    bahasa Inggris setelah dia ganti locale — dan tidak ada satu pun teks
    yang tersimpan dalam dua bahasa di database.

    Status "belum dibaca" disimpan sebagai atribut `data-notification-unread`
    pada <li>, bukan sebagai class — semua gayanya mengikuti atribut itu dari
    `app.css`, sehingga JS yang membuka lonceng cukup menghapus satu atribut
    per baris untuk menandai seluruh daftar sudah dibaca.

    Warna ikon: merah untuk keterlambatan/penolakan, hijau untuk
    pengembalian/persetujuan, indigo (warna aksen, bukan warna status) untuk
    sisanya — jadi maksimal dua keluarga warna status per layar, sesuai
    aturan design.md.
--}}
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Lang;

    $data = is_array($notification->data) ? $notification->data : [];
    $type = $data['type'] ?? null;
    $key = $type !== null ? 'notifications.types.'.$type : null;
    $hasText = $key !== null && Lang::has($key.'.title');

    $params = $data;

    // Null tidak pernah ikut sebagai pengganti placeholder: dirapikan jadi
    // string kosong supaya pesan tetap terbaca dan tidak ada str_replace
    // yang menerima null.
    foreach ($params as $column => $value) {
        if ($value === null) {
            $params[$column] = '';
        }
    }

    foreach (['due_at', 'borrowed_at', 'returned_at'] as $column) {
        if (! empty($params[$column])) {
            $params[$column] = Carbon::parse($params[$column])
                ->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'))
                ->translatedFormat('d M Y');
        }
    }

    /*
     * Judul buku dibungkus tanda kutip supaya tidak terbaca sebagai orang.
     * Contoh nyata: "Pahlawan yang Terlupakan" sekilas mirip "pustakawan",
     * jadi tanpa kutip kalimatnya bisa kebaca "pustakawan yang terlupakan".
     * Indonesia pakai kutip (“...”), Inggris pakai kutip ganda ("...").
     *
     * `book_title` selalu judul buku (semua Loan*.php). `subject` campuran —
     * hanya dikutip untuk tipe yang subject-nya pasti judul buku (notif buku
     * + peminjaman). Kalau semua subject dikutip, nama orang ikut berkutip,
     * mis. “Dede Dikri” — itu yang tidak kita mau.
     */
    $quote = app()->getLocale() === 'id' ? ['“', '”'] : ['"', '"'];

    if (! empty($params['book_title']) && is_string($params['book_title'])) {
        $params['book_title'] = $quote[0].$params['book_title'].$quote[1];
    }

    // Judul buku di notif jejak aksi dikirim lewat key `subject`
    // (lihat `ActionLogged`) — tapi key yang sama dipakai SEMUA objek
    // (nama kategori/penulis/penerbit/pengguna, ...). Hanya tipe yang
    // subject-nya pasti judul buku yang ikut dikutip: notif buku + peminjaman.
    $bookSubjectTypes = [
        'book_created', 'book_updated', 'book_deleted',
        'favorite_added', 'favorite_removed',
        'reading_saved', 'reading_removed',
        'loan_recorded', 'loan_renewed',
        'return_recorded', 'return_requested_self', 'loan_record_deleted',
    ];

    if ($type !== null && in_array($type, $bookSubjectTypes, true)
        && ! empty($params['subject']) && is_string($params['subject'])
        && ! str_starts_with($params['subject'], $quote[0])) {
        $params['subject'] = $quote[0].$params['subject'].$quote[1];
    }

    // Sanksi punya presisi jam (72 jam), bukan hari — formatnya ikut
    // lebih tajam supaya "sampai jam berapa" terbaca, bukan hanya tanggalnya.
    if (! empty($params['suspended_until'])) {
        $params['suspended_until'] = Carbon::parse($params['suspended_until'])
            ->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'))
            ->translatedFormat('d M Y H:i');
    }

    // Kalimat sanksi menyebut durasi (:hours). Payload notifikasi lama
    // (sebelum fitur sanksi) tidak membawa angka ini, jadi diisi dari
    // config saat render — pesan tidak boleh menyisakan ":hours" mentah.
    $params['hours'] = $params['hours'] ?? (int) config('perpustakaan.loan.suspension_hours', 72);

    // Alasan disimpan sebagai KODE (lihat LoanRejected::REASON_ADMIN_DELETED)
    // supaya bisa diterjemahkan di sini, saat notifikasi dibaca.
    if ($type === 'rejected') {
        $reason = $data['reason'] ?? null;
        $params['reason'] = is_string($reason) && $reason !== ''
            && Lang::has('notifications.reasons.'.$reason)
            ? __('notifications.reasons.'.$reason)
            : __('notifications.reasons.unknown');
    }

    $title = $hasText
        ? __($key.'.title')
        : ($data['book_title'] ?? __('notifications.generic_title'));
    $body = $hasText ? (string) __($key.'.body', $params) : (string) ($data['message'] ?? '');

    // Kalimat lanjutan hanya muncul kalau isinya memang relevan — pesan
    // sanksi pada pengembalian tepat waktu hanya membingungkan, dan
    // peringatan sanksi pada pengingat H-1 justru tujuannya: memberi
    // anggota satu hari terakhir untuk menghindari larangan meminjam.
    $extraKey = match (true) {
        $type === 'returned' && ! empty($params['suspended_until']) => $key.'.suspension_notice',
        $type === 'due_soon' && (int) ($data['renewals_left'] ?? 0) > 0 => $key.'.renewals',
        in_array($type, ['overdue_member', 'overdue_staff'], true)
            && (int) ($data['overdue_days'] ?? 0) > 0 => $key.'.suspension_notice',
        default => null,
    };

    if ($hasText && $extraKey !== null && Lang::has($extraKey)) {
        $body .= ' '.__($extraKey, $params);
    }

    $unread = $notification->read_at === null;

    $tone = match (true) {
        in_array($type, ['overdue_member', 'overdue_staff', 'rejected'], true) => [
            'class' => 'bg-overdue/10 text-overdue',
            'icon' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        ],
        in_array($type, ['returned', 'approved'], true) => [
            'class' => 'bg-available/10 text-available',
            'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z',
        ],
        default => [
            'class' => 'bg-tertiary/10 text-tertiary',
            'icon' => 'M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0',
        ],
    };
@endphp

<li{{ $unread ? ' data-notification-unread' : '' }}>
    <a href="{{ route('notifications.read', $notification) }}"
        class="notification-item flex gap-3 px-3 py-2.5 transition-colors hover:bg-secondary/5 focus-visible:bg-tertiary/10 focus-visible:outline-none">
        <span @class(['mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-full', $tone['class']]) aria-hidden="true">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tone['icon'] }}" />
            </svg>
        </span>

        <span class="min-w-0 flex-1">
            <span class="flex items-start gap-2">
                <span class="notification-title block text-sm">{{ $title }}</span>
                <span class="notification-dot mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-tertiary"
                    aria-hidden="true"></span>
            </span>

            @if ($body !== '')
                <span @class([
                    'mt-0.5 block text-xs leading-relaxed text-secondary',
                    'line-clamp-2' => $compact,
                    'line-clamp-3' => ! $compact,
                ])>{{ $body }}</span>
            @endif

            <span class="mt-1 block text-label text-secondary">
                {{ $notification->created_at->diffForHumans() }}
                <span class="sr-only notification-unread-label"> — {{ __('notifications.unread_marker') }}</span>
            </span>
        </span>
    </a>
</li>
