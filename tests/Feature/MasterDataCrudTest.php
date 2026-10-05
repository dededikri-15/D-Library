<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_book_create_form(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->get(route('books.create'))
            ->assertOk()
            ->assertSee('Tambah Buku');
    }

    public function test_anggota_cannot_open_book_create_form(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->get(route('books.create'))
            ->assertForbidden();
    }

    public function test_pustakawan_can_create_book(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => $book->title,
                'isbn' => '978-602-111-111-1',
                'description' => 'Deskripsi uji.',
                'publication_year' => 2023,
                'pages' => 250,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('books', ['isbn' => '978-602-111-111-1', 'title' => $book->title]);
    }

    public function test_staff_can_create_book_with_multiple_inventory_copies(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => $book->title,
                'isbn' => '978-602-111-112-8',
                'publication_year' => 2023,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
                'initial_copies' => 3,
            ])
            ->assertRedirect();

        $createdBook = Book::where('isbn', '978-602-111-112-8')->firstOrFail();

        $this->assertSame(3, $createdBook->copies()->count());
        $this->assertSame(3, $createdBook->copies()->distinct('inventory_code')->count());
        $this->assertSame(3, $createdBook->total_copies);
    }

    public function test_staff_can_add_inventory_copies_to_existing_book(): void
    {
        $book = Book::factory()->create();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('books.update', $book), [
                'title' => $book->title,
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'status' => $book->status,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
                'add_copies' => 2,
            ])
            ->assertRedirect();

        $this->assertSame(3, $book->copies()->count());
        $this->assertSame(3, $book->fresh()->total_copies);
        $this->assertSame(Book::STATUS_AVAILABLE, $book->fresh()->status);
    }

    public function test_added_copies_follow_inactive_book_status(): void
    {
        $book = Book::factory()->create(['status' => Book::STATUS_INACTIVE]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('books.update', $book), [
                'title' => $book->title,
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'status' => Book::STATUS_INACTIVE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
                'add_copies' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(2, $book->fresh()->total_copies);
        $this->assertSame(0, $book->availableCopies()->count());
    }

    public function test_book_create_rejects_duplicate_isbn(): void
    {
        $existing = Book::factory()->create(['isbn' => '978-602-222-222-2']);
        $new = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => 'Buku Kembar',
                'isbn' => '978-602-222-222-2',
                'publication_year' => 2023,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $new->category_id,
                'author_id' => $new->author_id,
                'publisher_id' => $new->publisher_id,
            ])
            ->assertSessionHasErrors('isbn');

        $this->assertSame(1, Book::where('isbn', $existing->isbn)->count());
    }

    public function test_book_create_allows_keeping_own_isbn_when_editing(): void
    {
        $book = Book::factory()->create(['isbn' => '978-602-333-333-3']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('books.update', $book), [
                'title' => 'Judul Baru',
                'isbn' => $book->isbn,
                'publication_year' => $book->publication_year,
                'pages' => $book->pages,
                'status' => $book->status,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertRedirect();

        $this->assertSame('Judul Baru', $book->fresh()->title);
    }

    public function test_publication_year_cannot_be_in_the_future(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => 'Buku Masa Depan',
                'isbn' => '978-602-444-444-4',
                'publication_year' => (int) now()->year + 5,
                'status' => Book::STATUS_AVAILABLE,
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertSessionHasErrors('publication_year');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $book = Book::factory()->make();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('books.store'), [
                'title' => 'Buku Status',
                'isbn' => '978-602-555-555-5',
                'publication_year' => 2023,
                'status' => 'status-ngawur',
                'category_id' => $book->category_id,
                'author_id' => $book->author_id,
                'publisher_id' => $book->publisher_id,
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_category_crud_and_auto_slug(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->post(route('categories.store'), [
                'name' => 'Ilmu Komputer',
                'description' => 'Buku seputar komputer.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'Ilmu Komputer', 'slug' => 'ilmu-komputer']);
    }

    public function test_category_slug_must_be_unique(): void
    {
        Category::factory()->create(['slug' => 'fiksi', 'name' => 'Fiksi']);
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->post(route('categories.store'), ['name' => 'Fiksi', 'slug' => 'fiksi'])
            ->assertSessionHasErrors('slug');
    }

    /**
     * `categories.name` unik di database. Tanpa aturan `unique` di
     * `CategoryRequest`, nama kembar lolos validasi lalu meledak jadi
     * QueryException dan HTTP 500, bukan pesan error yang bisa dibaca.
     */
    public function test_category_name_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Sains', 'slug' => 'sains']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('categories.store'), ['name' => 'Sains', 'slug' => 'sains-kembar'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Category::where('name', 'Sains')->count());
        $this->assertDatabaseMissing('categories', ['slug' => 'sains-kembar']);
    }

    public function test_pustakawan_can_update_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Sains Lama',
            'slug' => 'sains-lama',
            'description' => 'Deskripsi lama.',
        ]);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('categories.update', $category), [
                'name' => 'Sains Baru',
                'slug' => 'sains-baru',
                'description' => 'Deskripsi baru.',
            ])
            ->assertRedirect(route('categories.index'));

        $category->refresh();

        $this->assertSame('Sains Baru', $category->name);
        $this->assertSame('sains-baru', $category->slug);
        $this->assertSame('Deskripsi baru.', $category->description);
    }

    public function test_category_update_keeps_own_slug(): void
    {
        $category = Category::factory()->create(['name' => 'Sains', 'slug' => 'sains']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('categories.update', $category), [
                'name' => 'Sains',
                'slug' => 'sains',
            ])
            ->assertRedirect();

        $this->assertSame('sains', $category->fresh()->slug);
        $this->assertSame('Sains', $category->fresh()->name);
    }

    public function test_pustakawan_can_delete_category(): void
    {
        $category = Category::factory()->create(['name' => 'Kategori Dihapus']);

        $this->actingAs(User::factory()->pustakawan()->create())
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_anggota_cannot_create_category(): void
    {
        $this->actingAs(User::factory()->anggota()->create())
            ->post(route('categories.store'), ['name' => 'Menyusup'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Menyusup']);
    }

    public function test_pustakawan_can_create_user(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('users.store'), [
                'name' => 'Anggota Baru',
                'email' => 'anggota.baru@example.com',
                'gender' => User::GENDER_PEREMPUAN,
                'password' => 'rahasia-kuat-123',
                'password_confirmation' => 'rahasia-kuat-123',
                'role' => User::ROLE_ANGGOTA,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'anggota.baru@example.com',
            'role' => User::ROLE_ANGGOTA,
            'gender' => User::GENDER_PEREMPUAN,
        ]);
    }

    public function test_user_forms_show_gender_and_current_choice(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $member = User::factory()->anggota()->create([
            'gender' => User::GENDER_PEREMPUAN,
        ]);

        $this->actingAs($staff)
            ->get(route('users.create'))
            ->assertOk()
            ->assertSee('name="gender"', false)
            ->assertSee('Laki-laki')
            ->assertSee('Perempuan');

        $this->actingAs($staff)
            ->get(route('users.edit', $member))
            ->assertOk()
            ->assertSee('value="perempuan" selected', false);
    }

    public function test_pustakawan_cannot_assign_removed_admin_role(): void
    {
        $member = User::factory()->anggota()->create();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->put(route('users.update', $member), [
                'name' => $member->name,
                'email' => $member->email,
                'gender' => $member->gender,
                'role' => 'admin',
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame(User::ROLE_ANGGOTA, $member->fresh()->role);
    }

    public function test_pustakawan_can_change_user_role(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $anggota = User::factory()->anggota()->create();

        $this->actingAs($staff)
            ->put(route('users.update', $anggota), [
                'name' => $anggota->name,
                'email' => $anggota->email,
                'gender' => User::GENDER_PEREMPUAN,
                'role' => User::ROLE_PUSTAKAWAN,
            ])
            ->assertRedirect();

        $this->assertSame(User::ROLE_PUSTAKAWAN, $anggota->fresh()->role);
        $this->assertSame(User::GENDER_PEREMPUAN, $anggota->fresh()->gender);
    }

    public function test_editing_user_without_password_keeps_existing_password(): void
    {
        $staff = User::factory()->pustakawan()->create();
        $anggota = User::factory()->anggota()->create();
        $originalHash = $anggota->password;

        $this->actingAs($staff)
            ->put(route('users.update', $anggota), [
                'name' => 'Nama Baru',
                'email' => $anggota->email,
                'gender' => $anggota->gender,
                'role' => User::ROLE_ANGGOTA,
            ])
            ->assertRedirect();

        $anggota->refresh();
        $this->assertSame('Nama Baru', $anggota->name);
        $this->assertSame($originalHash, $anggota->password);
    }

    public function test_pustakawan_cannot_delete_own_account(): void
    {
        $staff = User::factory()->pustakawan()->create();

        $this->actingAs($staff)
            ->delete(route('users.destroy', $staff))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $staff->id]);
    }
}
