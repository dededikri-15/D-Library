<?php

namespace Tests\Feature;

use App\Notifications\ActionLogged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesLibraryUsers;
use Tests\TestCase;

class ActionNotificationDisplayTest extends TestCase
{
    use CreatesLibraryUsers;
    use RefreshDatabase;

    /* ------------------------------------------------------------------
     | Penampilan: judul dari lang + klik ke URL tujuan
     * ----------------------------------------------------------------- */

    public function test_halaman_notifikasi_menampilkan_judul_tipe_aksi_dari_lang(): void
    {
        $member = $this->member();

        // Tanpa Notification::fake(): baris benar-benar ditulis ke database
        // supaya halaman riwayat bisa merendernya.
        $member->notify(new ActionLogged(
            'book_created',
            ['subject' => 'Buku Judul Tes'],
            route('books.index'),
        ));

        $this->actingAs($member)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Buku ditambahkan')
            ->assertSee('Buku Judul Tes');
    }

    public function test_membuka_notifikasi_jejak_aksi_mengarahkan_ke_url_payload(): void
    {
        $member = $this->member();

        $member->notify(new ActionLogged(
            'category_created',
            ['subject' => 'Kategori Tes'],
            route('categories.public'),
        ));

        $notification = $member->notifications()->firstOrFail();

        $this->actingAs($member)
            ->get(route('notifications.read', $notification))
            ->assertRedirect(route('categories.public'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_payload_action_logged_membawa_tipe_param_dan_url(): void
    {
        $member = $this->member();

        $notification = new ActionLogged(
            'favorite_added',
            ['subject' => 'Buku Favorit'],
            route('favorites.index'),
        );

        $data = $notification->toArray($member);

        $this->assertSame('favorite_added', $data['type']);
        $this->assertSame('Buku Favorit', $data['subject']);
        $this->assertSame(route('favorites.index'), $data['url']);
    }
}
