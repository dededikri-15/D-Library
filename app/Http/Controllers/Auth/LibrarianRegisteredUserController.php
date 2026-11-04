<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LibrarianRegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class LibrarianRegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.librarian_register');
    }

    public function store(LibrarianRegisterRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['avatar']);

        /** @var UploadedFile|null $avatar */
        $avatar = $request->file('avatar');

        $librarian = User::create([
            ...$data,
            'role' => User::ROLE_PUSTAKAWAN,
            'avatar' => $avatar?->store('avatars', User::avatarDisk()),
        ]);

        $this->notifySelf(
            $request->user(),
            'user_created',
            ['subject' => $librarian->name],
            route('users.index'),
        );

        return $this->success('users.index', __('messages.user_created'));
    }
}
