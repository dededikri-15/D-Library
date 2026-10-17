<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Notifications\LoanReturned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\ProvidesRenewalFixtures;
use Tests\TestCase;

class LoanSuspensionNoticeTest extends TestCase
{
    use ProvidesRenewalFixtures;
    use RefreshDatabase;

    public function test_tombol_pinjam_nonaktif_selama_sanksi(): void
    {
        $member = User::factory()->anggota()->suspended()->create();
        $book = Book::factory()->create(['status' => Book::STATUS_AVAILABLE]);

        $content = $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(route('books.borrow', $book), $content);
        $this->assertStringContainsString('masa sanksi', $content);
    }

    public function test_pengembalian_terlambat_mengirim_notifikasi_sanksi_ke_anggota(): void
    {
        $loan = $this->overdueLoan();

        $this->actingAs(User::factory()->pustakawan()->create())
            ->post(route('loans.return', $loan))
            ->assertSessionHas('status');

        $notification = $loan->user->refresh()->notifications()
            ->where('type', LoanReturned::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($notification);
        $this->assertNotNull($notification->data['suspended_until']);
        $this->assertTrue(
            Carbon::parse($notification->data['suspended_until'])
                ->equalTo($loan->user->suspended_until),
        );
    }
}
