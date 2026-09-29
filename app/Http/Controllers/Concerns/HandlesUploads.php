<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Book;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Menyimpan, mengganti, dan menghapus berkas upload.
 *
 * Aturan yang dipakai bersama oleh Buku (cover + PDF) dan Penulis (foto):
 *
 * 1. Nama file SELALU di-generate ulang (bergaya hash), bukan memakai nama
 *    asli dari user. Nama asli bisa berisi spasi, karakter non-ASCII, atau
 *    hal berbahaya seperti `../../` sehingga tidak aman disimpan apa adanya.
 * 2. Cover & foto memakai disk publik, file PDF memakai disk privat.
 * 3. Berkas lama SELALU dihapus dari disk saat diganti atau saat record
 *    dihapus, supaya tidak menumpuk di storage.
 */
trait HandlesUploads
{
    /**
     * Simpan berkas baru dan hapus berkas lama bila ada.
     *
     * @param  string  $disk  disk tujuan
     * @param  string  $directory  folder di dalam disk
     * @param  string|null  $oldPath  path lama yang harus dibersihkan
     * @return string|null path baru, atau null bila tidak ada yang diunggah
     */
    protected function storeUpload(?UploadedFile $file, string $disk, string $directory, ?string $oldPath = null): ?string
    {
        if (! $file) {
            return null;
        }

        $path = $file->store($directory, $disk);

        $this->deleteUpload($oldPath, $disk);

        return $path;
    }

    /**
     * Hapus berkas dari disk bila ada. Kegagalan dihentikan diam-diam:
     * file yang sudah hilang tidak boleh menggagalkan penyimpanan data.
     */
    protected function deleteUpload(?string $path, string $disk): void
    {
        if (blank($path)) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable) {
            // Berkas sudah tidak ada, atau disk tidak terjangkau. Abaikan.
        }
    }

    /**
     * Kumpulkan path cover & file PDF untuk disimpan ke database.
     *
     * Urutan yang dipakai controller:
     *   $data = $request->safe()->except(['cover', 'file', 'remove_cover', 'remove_file']);
     *   $data += $this->bookFilePayload($request, $book);
     *
     * Perilaku:
     * - Ada file baru  -> path baru, berkas lama dihapus.
     * - `remove_*` dicentang dan tidak ada file baru -> kolom jadi null, berkas dihapus.
     * - Tidak ada file baru & `remove_*` tidak dicentang -> path lama dipertahankan.
     *
     * @return array<string, string|null>
     */
    protected function bookFilePayload(object $request, ?Book $book = null): array
    {
        $payload = [];

        $this->applyUploadField(
            $payload, 'cover', $request, $book?->cover, Book::coverDisk(), 'covers'
        );
        $this->applyUploadField(
            $payload, 'file', $request, $book?->file, Book::bookFileDisk(), 'books'
        );

        return $payload;
    }

    /**
     * @param  array<string, string|null>  $payload
     */
    private function applyUploadField(
        array &$payload,
        string $field,
        object $request,
        ?string $oldPath,
        string $disk,
        string $directory
    ): void {
        /** @var UploadedFile|null $upload */
        $upload = $request->file($field);

        if ($upload) {
            $payload[$field] = $this->storeUpload($upload, $disk, $directory, $oldPath);

            return;
        }

        // Checkbox "hapus cover" / "hapus file" dicentang.
        if ($request->boolean('remove_'.$field)) {
            $this->deleteUpload($oldPath, $disk);
            $payload[$field] = null;
        }
    }
}
