<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LoadsBookOptions;
use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen buku untuk staf: create, store, edit, update, destroy (PRD §6).
 *
 * Katalog publik & pratinjau ada di BookCatalogController; halaman baca dan
 * stream PDF ada di BookReaderController. Pemisahan ini membuat tiap file
 * berukuran wajar dengan satu tanggung jawab yang jelas.
 */
class BookController extends Controller
{
    use LoadsBookOptions;

    public function create(): View
    {
        return view('books.create', [
            'book' => new Book,
            ...$this->formOptions(),
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        $book = Book::create($this->bookPayload($request));
        $this->createCopies(
            $book,
            max(0, $request->integer('initial_copies', 1) - 1),
            $book->status,
        );

        $this->notifySelf(
            $request->user(),
            'book_created',
            ['subject' => $book->title],
            route('books.show', $book),
        );

        return $this->success('books.show', __('messages.book_created'), ['book' => $book]);
    }

    public function edit(Book $book): View
    {
        return view('books.edit', [
            'book' => $book,
            'copies' => $book->copies()->orderBy('inventory_code')->get(),
            ...$this->formOptions(),
        ]);
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $wasInactive = $book->status === Book::STATUS_INACTIVE;
        $book->update($this->bookPayload($request, $book));
        $status = $book->status;

        /*
         * `getChanges()` hanya mencerminkan save TERAKHIR. Karena method ini
         * menyimpan dua kali (payload lalu status turunan), perubahan payload
         * harus ditangkap di sini — sebelum save kedua menimpanya.
         */
        $payloadChanged = collect($book->getChanges())->except('updated_at')->isNotEmpty();

        if ($status === Book::STATUS_INACTIVE) {
            $book->availableCopies()->update(['status' => BookCopy::STATUS_INACTIVE]);
            $this->createCopies($book, $request->integer('add_copies'), BookCopy::STATUS_INACTIVE);
        } else {
            if ($wasInactive) {
                $book->copies()->where('status', BookCopy::STATUS_INACTIVE)
                    ->update(['status' => BookCopy::STATUS_AVAILABLE]);
            }

            $this->createCopies($book, $request->integer('add_copies'));
            $book->update([
                'status' => $book->availableCopies()->exists()
                    ? Book::STATUS_AVAILABLE
                    : Book::STATUS_BORROWED,
            ]);
        }

        // Hanya catat jejak kalau benar-benar ada kolom yang berubah. Form
        // edit yang dikirim tanpa perubahan apa pun tidak boleh menghasilkan
        // notifikasi "Buku diperbarui" yang menyesatkan.
        $statusChanged = collect($book->getChanges())->except('updated_at')->isNotEmpty();

        if ($payloadChanged || $statusChanged) {
            $this->notifySelf(
                $request->user(),
                'book_updated',
                ['subject' => $book->title],
                route('books.show', $book),
            );
        }

        return $this->success('books.show', __('messages.book_updated'), ['book' => $book]);
    }

    /**
     * Selesaikan payload buku dari request yang sudah tervalidasi.
     *
     * Field upload dipisah karena tidak boleh ikut `validated()` apa adanya:
     * `cover` dan `file` dipegang trait `HandlesUploads` (nama file di-generate
     * ulang, file lama dihapus saat diganti), sedangkan `remove_cover` dan
     * `remove_file` adalah flag UI tanpa kolom sendiri.
     *
     * @return array<string, mixed>
     */
    protected function bookPayload(BookRequest $request, ?Book $book = null): array
    {
        return $request->safe()->except([
            'cover',
            'file',
            'remove_cover',
            'remove_file',
            'initial_copies',
            'add_copies',
        ])
            + $this->bookFilePayload($request, $book);
    }

    protected function createCopies(Book $book, int $count, string $status = BookCopy::STATUS_AVAILABLE): void
    {
        for ($index = 0; $index < $count; $index++) {
            $book->copies()->create(['status' => $status]);
        }

        if ($count > 0) {
            $book->increment('total_copies', $count);
        }
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        // Hapus berkas fisik dulu supaya tidak ada file yatim di storage
        // walau proses hapus baris database gagal di tengah jalan.
        $this->deleteUpload($book->cover, $book::coverDisk());
        $this->deleteUpload($book->file, $book::bookFileDisk());

        $title = $book->title;

        $book->delete();

        $this->notifySelf(
            $request->user(),
            'book_deleted',
            ['subject' => $title],
            route('books.index'),
        );

        return $this->success('books.index', __('messages.book_deleted'));
    }
}
