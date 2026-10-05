<?php

namespace App\Listeners;

use App\Models\UserActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class RecordUserActivity
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function handleLogin(Login $event): void
    {
        $this->record($event->user->getAuthIdentifier(), UserActivity::TYPE_LOGIN);
    }

    public function handleLogout(Logout $event): void
    {
        if ($event->user) {
            $this->record($event->user->getAuthIdentifier(), UserActivity::TYPE_LOGOUT);
        }
    }

    private function record(int|string $userId, string $type): void
    {
        UserActivity::create([
            'user_id' => $userId,
            'type' => $type,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
