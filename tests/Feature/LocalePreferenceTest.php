<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_select_english_and_preference_applies_to_next_request(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('Thousands of books,')
            ->assertSee('>Home</span>', false)
            ->assertSee('System active')
            ->assertSee('Service Hours')
            ->assertSee('Monday–Friday, 8:00 AM–4:00 PM');
    }

    public function test_guest_can_select_indonesian(): void
    {
        $this->withSession(['locale' => 'en'])
            ->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'id'])
            ->assertRedirect(route('home'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="id"', false)
            ->assertSee('Akses ribuan buku,')
            ->assertSee('>Beranda</span>', false)
            ->assertSee('Sistem Aktif');
    }

    public function test_unsupported_locale_is_rejected_without_changing_preference(): void
    {
        $this->withSession(['locale' => 'id'])
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<html lang="id"', false);
    }

    public function test_dashboard_and_status_labels_follow_selected_english_locale(): void
    {
        $librarian = User::factory()->pustakawan()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_BORROWED]);
        Loan::factory()->create(['book_id' => $book->id]);

        $this->actingAs($librarian)
            ->withSession(['locale' => 'en'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Librarian Dashboard')
            ->assertSee('Total Books')
            ->assertSee('Recent Loans')
            ->assertSee('Borrowed')
            ->assertSee('Librarian');

        $member = User::factory()->anggota()->create(['name' => 'Test Member']);

        $this->actingAs($member)
            ->withSession(['locale' => 'en'])
            ->get(route('anggota.dashboard'))
            ->assertOk()
            ->assertSee('Hello, Test Member')
            ->assertSee('Active Loans')
            ->assertSee('Loan history');
    }

    public function test_auth_catalog_and_book_detail_follow_selected_english_locale(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Log in')
            ->assertSee('Remember me');

        $this->withSession(['locale' => 'en'])
            ->get(route('register'))
            ->assertOk()
            ->assertSee('Join the Library')
            ->assertSee('Full name');

        $this->withSession(['locale' => 'en'])
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee('Forgot Password')
            ->assertSee('Send Reset Link');

        $this->withSession(['locale' => 'en'])
            ->get(route('password.reset', ['token' => 'locale-test-token']))
            ->assertOk()
            ->assertSee('Reset Password')
            ->assertSee('Confirm password');

        $this->withSession(['locale' => 'en'])
            ->get(route('books.index'))
            ->assertOk()
            ->assertSee('Book Catalog')
            ->assertSee('Search title, ISBN, author, category, or publisher')
            ->assertSee('Apply filters');

        $book = Book::factory()->create();

        $this->withSession(['locale' => 'en'])
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Synopsis')
            ->assertSee('Copies available')
            ->assertSee('Available to borrow');
    }

    public function test_category_and_loan_pages_follow_selected_english_locale(): void
    {
        Category::factory()->create();

        $this->withSession(['locale' => 'en'])
            ->get(route('categories.public'))
            ->assertOk()
            ->assertSee('Book Categories')
            ->assertSee('Choose a category to filter the catalog.');

        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create();
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)
            ->withSession(['locale' => 'en'])
            ->get(route('loans.mine'))
            ->assertOk()
            ->assertSee('Loan History')
            ->assertSee('Book')
            ->assertSee('Request return');

        $this->actingAs(User::factory()->pustakawan()->create())
            ->withSession(['locale' => 'en'])
            ->get(route('loans.index'))
            ->assertOk()
            ->assertSee('Filter by status')
            ->assertSee('Member')
            ->assertSee('Loans');
    }

    public function test_member_pages_and_pdf_reader_follow_selected_english_locale(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create([
            'title' => 'Digital Book Test',
            'file' => 'books/digital-book-test.pdf',
        ]);

        Favorite::factory()->create(['user_id' => $member->id, 'book_id' => $book->id]);
        ReadingHistory::create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'last_page' => 4,
            'last_read_at' => now(),
        ]);
        Loan::factory()->create([
            'user_id' => $member->id,
            'book_id' => $book->id,
            'status' => Loan::STATUS_BORROWED,
        ]);

        $this->actingAs($member)->withSession(['locale' => 'en'])
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Favorite Books')
            ->assertSee('Remove from favorites');

        $this->actingAs($member)->withSession(['locale' => 'en'])
            ->get(route('reading-histories.index'))
            ->assertOk()
            ->assertSee('Reading History')
            ->assertSee('Last page')
            ->assertSee('Continue reading');

        $this->actingAs($member)->withSession(['locale' => 'en'])
            ->get(route('mailbox.index'))
            ->assertOk()
            ->assertSee('Sent notification emails')
            ->assertSee('No emails yet');

        $this->actingAs($member)->withSession(['locale' => 'en'])
            ->get(route('books.read', $book))
            ->assertOk()
            ->assertSee('Back to book details')
            ->assertSee('Save reading position');
    }

    public function test_controller_flash_messages_follow_selected_locale(): void
    {
        $this->actingAs(User::factory()->pustakawan()->create())
            ->withSession(['locale' => 'en'])
            ->post(route('categories.store'), ['name' => 'Locale Category'])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('status', 'Category added successfully.');
    }
}
