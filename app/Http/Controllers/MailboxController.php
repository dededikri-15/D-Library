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
        $message = DB::table('mail_messages')->findOrFail($id);

        return view('mailbox.show', [
            'message' => $message,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        DB::table('mail_messages')->where('id', $id)->delete();

        return redirect()->route('mailbox.index')->with('success', 'Email berhasil dihapus.');
    }
}
