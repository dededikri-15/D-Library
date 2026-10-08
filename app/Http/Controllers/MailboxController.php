<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MailboxController extends Controller
{
    public function index(Request $request): View
    {
        $messages = DB::table('mail_messages')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('mailbox.index', [
            'messages' => $messages,
        ]);
    }

    public function show(int $id): View
    {
        // Query Builder polos tidak punya `findOrFail` (lihat catatan di destroy).
        $message = DB::table('mail_messages')->where('id', $id)->first();

        abort_if($message === null, 404);

        return view('mailbox.show', [
            'message' => $message,
        ]);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        // `DB::table` memakai Query Builder polos, bukan Eloquent — `findOrFail`
        // tidak tersedia di sana. `first()` lalu dicek manual.
        $message = DB::table('mail_messages')->where('id', $id)->first();

        abort_if($message === null, 404);

        DB::table('mail_messages')->where('id', $id)->delete();

        $this->notifySelf(
            $request->user(),
            'mail_deleted',
            ['subject' => $message->subject],
            route('mailbox.index'),
        );

        return redirect()->route('mailbox.index')->with('success', 'Email berhasil dihapus.');
    }
}
