<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Json;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Daftar dan hapus untuk master data sederhana (kategori, penulis, penerbit).
 *
 * Ditempel di kelas induk `Controller` supaya ketiga controller pewaris
 * mendapatkannya tanpa `use` ulang — sama seperti HandlesUploads dan
 * RespondsWithFlash (lihat catatan di Controller.php).
 */
trait ManagesMasterData
{
    /**
     * Halaman daftar untuk master data sederhana.
     *
     * Kategori, penulis, dan penerbit punya bentuk yang persis sama: satu
     * kotak cari di kolom `name`, urutan `name`, dan jumlah buku per baris.
     * Setelah ditulis tiga kali, ketiganya mulai berbeda tipis - satu lupa
     * `withQueryString()` sehingga filter hilang saat pindah halaman, satu
     * berubah urutan. Satu implementasi membuat ketiganya pasti sama.
     *
     * Nama variabel hasil tetap diteruskan apa adanya karena setiap view
     * memang memakainya dengan nama sendiri (`$categories`, `$authors`,
     * `$publishers`).
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function masterIndex(Request $request, string $modelClass, string $view, string $variable): View
    {
        $filters = $this->validFilters($request, ['q' => ['nullable', 'string', 'max:150']]);

        $rows = $modelClass::query()
            ->withCount('books')
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $this->applySearch($query, 'name', $q))
            ->orderBy('name')
            ->paginate(config('perpustakaan.pagination.per_page'))
            // Tanpa ini, filter di URL hilang begitu user menekan halaman 2.
            ->withQueryString();

        return view($view, [$variable => $rows]);
    }

    /**
     * Hapus satu baris master data, sekalian buku-buku yang memakainya.
     *
     * Kenapa buku ikut terhapus: `books.category_id` (dan dua kolom lain di
     * tabel `books`) memakai `cascadeOnDelete()`. Begitu baris master data
     * hilang, PostgreSQL ikut menghapus semua buku yang menunjuknya — lalu
     * `loans`, `favorites`, dan `reading_histories` yang menempel ke buku-buku
     * itu ikut hilang lagi. Jadi "hapus kategori" berarti "hapus kategori
     * beserta isi dan riwayatnya", dan itu harus disadari orang yang menekan
     * tombolnya, bukan hasil diam-diam.
     *
     * Karena itu dua hal berubah dibanding sebelumnya:
     *
     * 1. Hapus TIDAK lagi ditolak selama masih ada buku yang memakainya.
     *    Menolak membuat pustakawan tidak bisa membereskan kategori yang salah
     *    input tanpa membuka tiap buku satu per satu.
     * 2. Berkas fisik tiap buku yang ikut terhapus harus dihapus dari disk
     *    DULUAN baris database-nya. Cascade di database tidak mengenal
     *    storage: tanpa langkah ini `storage/app` akan penuh cover dan PDF
     *    yatim yang tidak pernah dirujuk siapa pun, dan ukurannya tidak
     *    pernah berkurang.
     *
     * Urutan "file dulu, baris menyusul" sama dengan
     * `BookController::destroy()`. Kalau dibalik dan prosesnya mati di tengah,
     * yang tersisa baris buku tanpa berkas — lebih sulit diperbaiki
     * daripada berkas sisa tanpa baris, karena baris/book yang hilang tidak
     * bisa dipulihkan dari backup storage.
     *
     * `$beforeDelete` hanya dipanggil kalau data benar-benar dihapus. Penulis
     * butuh itu untuk berkas fotonya.
     *
     * Pengecekan jumlah buku dan penghapusan sengaja tidak di(transaction)-kan.
     * Keduanya harus konsisten dengan apa yang dilihat user di tabel daftar
     * (yang menampilkan jumlah buku yang sama), dan selisih sesaat antara
     * "dihitung 3 buku" dan "dihapus" hanya bisa terjadi kalau dua pustakawan
     * menekan hapus pada milidetik yang sama.
     *
     * @param  string  $label  nama jenis data dalam kalimat, mis. "kategori"
     */
    protected function destroyMasterData(
        Model $row,
        string $label,
        string $indexRoute,
        ?Closure $beforeDelete = null,
    ): RedirectResponse {
        $books = $row->books()->get(['id', 'cover', 'file']);

        foreach ($books as $book) {
            $this->deleteUpload($book->cover, $book::coverDisk());
            $this->deleteUpload($book->file, $book::bookFileDisk());
        }

        $beforeDelete?->__invoke();

        $row->delete();

        $message = $books->isEmpty()
            ? ucfirst($label).' berhasil dihapus.'
            : ucfirst($label).' berhasil dihapus, termasuk '.$books->count().' buku di dalamnya.';

        return $this->success($indexRoute, $message);
    }

    /**
     * Simpan cepat (quick add) satu baris master data dari dalam form buku.
     *
     * Kenapa dipusatkan di sini: CategoryController, AuthorController, dan
     * PublisherController sebelumnya punya method `storeQuick` yang bentuknya
     * sama persis — validasi `name`, buat baris, catat notifikasi, balas
     * JSON `{id, name}` untuk dropdown quick add. Yang berbeda hanya label
     * nama field dalam bahasa Indonesia, panjang maksimum, aturan unique,
     * dan cara barisnya dibuat (kategori ikut mengisi slug). Perbedaan itu
     * diserahkan lewat parameter, jadi ketiga controller tinggal memanggil
     * dengan miliknya dan aturan mainnya tetap satu.
     *
     * @param  Closure(string): Model  $create  membuat baris dari nama yang sudah tervalidasi
     */
    protected function storeQuickRow(
        Request $request,
        Closure $create,
        string $attributeLabel,
        string $indexRoute,
        ?string $uniqueInTable = null,
        int $maxNameLength = 150,
    ): JsonResponse {
        $rules = ['required', 'string', 'max:'.$maxNameLength];

        if ($uniqueInTable !== null) {
            $rules[] = Rule::unique($uniqueInTable, 'name');
        }

        // Validasi inline tetap perlu `attributes` sendiri: tanpa itu pesan
        // errornya menyebut field mentah "name", bukan "nama kategori".
        $validated = $request->validate(
            ['name' => $rules],
            attributes: ['name' => $attributeLabel],
        );

        $row = $create($validated['name']);

        $this->notifySelf(
            $request->user(),
            // 'Category' -> 'category_created' — kunci notifikasi yang sama
            // persis dengan yang dipakai ketiga controller sebelumnya.
            Str::snake(class_basename($row)).'_created',
            ['subject' => $row->name],
            $indexRoute,
        );

        return Json::response([
            'id' => $row->id,
            'name' => $row->name,
        ]);
    }
}
