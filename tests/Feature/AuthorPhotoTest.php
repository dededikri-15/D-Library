<?php

namespace Tests\Feature;

use App\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class AuthorPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk publik disihkan, disk privat memakai array storage supaya test
        // tidak menyentuh storage/app/private yang sedang dipakai dev.
        Storage::fake('public');
        Storage::fake('local');
    }

    use CreatesLibraryUsers;

    public function test_deleting_author_deletes_photo(): void
    {
        $this->actingAs($this->staff())->post(route('authors.store'), [
            'name' => 'Andrea Hirata',
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $author = Author::firstOrFail();
        $photo = $author->photo;

        $this->actingAs($this->staff())->delete(route('authors.destroy', $author));

        $this->assertDatabaseMissing('authors', ['id' => $author->id]);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_author_photo_must_be_an_image(): void
    {
        $this->actingAs($this->staff())
            ->post(route('authors.store'), [
                'name' => 'Salah Upload',
                'photo' => UploadedFile::fake()->create('script.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('authors', 0);
    }
}
