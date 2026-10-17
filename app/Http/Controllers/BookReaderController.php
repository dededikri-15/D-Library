<?php

namespace App\Http\Controllers;

use App\Actions\RecordReading;
use App\Models\Book;
use App\Models\ReadingHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembaca digital (PRD §10, Task 11.1 & 11.2).
 *
 * Route: books.read (/buku/{book}/baca) dan books.file (/buku/{book}/berkas).
 * Berkas PDF TIDAK pernah tersedia sebagai URL publik: halaman hanya
 * menampilkan objek yang menunjuk ke route `books.file`, dan route itulah
 * yang memeriksa hak akses lalu me-stream berkasnya.
 */
class BookReaderController extends Controller
{
    public function __construct(
        protected RecordReading $recordReading,
    ) {
        //
    }

    /**
     * Halaman pembaca digital (PRD §10, Task 11.1 & 11.2).
     *
     * Membuka halaman ini dicatat sebagai kunjungan (lihat RecordReading).
     * Yang dicatat hanya waktu baca, bukan posisi: memundurkan posisi ke
     * halaman 1 setiap kali buku dibuka akan membuat fitur "lanjut membaca"
     * tidak pernah berguna.
     */
    public function read(Request $request, Book $book): View|RedirectResponse
    {
        if (! $book->hasFile()) {
            return $this->warning('books.show', __('messages.book_file_unavailable'), ['book' => $book]);
        }

        $user = $request->user();

        if (! $book->canBeReadBy($user)) {
            abort(403, 'Anda tidak punya izin membaca buku ini.');
        }

        /*
         * `last_page` harus di-cast ke int secara eksplisit. Kolomnya punya
         * default 0 di database, tapi saat baris baru dibuat Eloquent tidak
         * menyertakan kolom yang tidak ada di array insert, jadi atributnya
         * `null` di objek walau database sudah berisi 0. Tanpa cast ini,
         * pembacaan pertama kali akan jadi HTTP 500 (TypeError).
         */
        $existing = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();

        $savedPage = (int) ($existing?->last_page ?? 0);

        // `?page=` dari "lanjut membaca" menang atas posisi tersimpan, karena
        // user yang menekan tautan itu memang sedang mencari halaman tertentu.
        $requested = $request->integer('page');
        $page = $this->recordReading->clampPage($requested > 0 ? $requested : $savedPage, $book);

        // Dicatat setelah halaman dihitung, supaya kunjungan pertama kali
        // mengingat halaman yang benar-benar dibuka.
        //
        // Hanya untuk anggota: staff memakai reader ini untuk pratinjau buku,
        // dan tidak ada halaman "riwayat baca" yang menampilkan data mereka,
        // jadi barisnya cuma jadi sampah data.
        if ($user->isAnggota()) {
            $this->recordReading->visit($user, $book, $page);
        }

        return view('books.read', [
            'book' => $book,
            'page' => $page,
            'totalPages' => (int) $book->pages > 0 ? (int) $book->pages : null,
            'savedPage' => $savedPage,
        ]);
    }

    /**
     * Stream berkas PDF untuk pembaca.
     *
     * Sengaja memakai `Content-Disposition: inline` (bukan `attachment`) supaya
     * PDF tampil di tab browser, dan file tetap di-stream dari disk privat —
     * tidak pernah ada URL publik yang bisa ditebak.
     *
     * Karena tombol "Buka halaman" memuat ulang elemen `<object>` di sisi
     * browser (lihat `reloadReaderTo()` di resources/js/app.js), endpoint ini
     * selalu dikunjungi setiap berpindah halaman. Tanpa header cache, tiap
     * kunjungan berarti unduhan penuh sekaligus memakai kuota rate limit 30
     * permintaan per menit. `private` + `max-age` pendek membuat browser boleh
     * memakai salinannya sendiri selama cache berlaku, dan ETag/Last-Modified
     * memungkinkan jawaban 304 (tanpa isi) setelahnya.
     */
    public function file(Request $request, Book $book): Response
    {
        if (! $book->canBeReadBy($request->user())) {
            abort(403, 'Anda tidak punya izin membaca buku ini.');
        }

        $disk = Storage::disk($book::bookFileDisk());

        if (! $disk->exists($book->file)) {
            abort(404, 'File digital buku ini tidak ditemukan.');
        }

        $lastModified = (int) $disk->lastModified($book->file);
        $etag = '"'.md5($lastModified.':'.$disk->size($book->file)).'"';
        $cacheControl = 'private, max-age=300, must-revalidate';
        $lastModifiedHttp = gmdate('D, d M Y H:i:s', $lastModified).' GMT';

        if ($this->isNotModified($request, $etag, $lastModified)) {
            return new Response('', 304, [
                'ETag' => $etag,
                'Last-Modified' => $lastModifiedHttp,
                'Cache-Control' => $cacheControl,
            ]);
        }

        return $disk->response(
            $book->file,
            $book->title.'.pdf',
            [
                // inline = tampil di browser, attachment = diunduh.
                'Content-Disposition' => 'inline; filename="'.addslashes($book->title).'.pdf"',
                'ETag' => $etag,
                'Last-Modified' => $lastModifiedHttp,
                'Cache-Control' => $cacheControl,
            ],
        );
    }

    /**
     * Apakah klien sudah menyimpan salinan yang sama persis dengan milik kita?
     *
     * `If-None-Match` (ETag) menang atas `If-Modified-Since` — begitu
     * prioritasnya di RFC 9110, dan ETag kami berubah begitu berkasnya
     * diganti, sedangkan waktu bisa sama meski isinya beda.
     */
    private function isNotModified(Request $request, string $etag, int $lastModified): bool
    {
        if ($request->headers->has('If-None-Match')) {
            $candidates = array_map(trim(...), explode(',', (string) $request->headers->get('If-None-Match')));

            return in_array('*', $candidates, true) || in_array($etag, $candidates, true);
        }

        $since = strtotime((string) $request->headers->get('If-Modified-Since'));

        return $since !== false && $since >= $lastModified;
    }
}
