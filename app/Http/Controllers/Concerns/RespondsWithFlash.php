<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

/**
 * Helper respons supaya setiap controller berperilaku sama: setelah aksi
 * tulis, user selalu dikembalikan ke halaman yang relevan dengan flash message.
 */
trait RespondsWithFlash
{
    /**
     * @param  array<string, mixed>  $params
     */
    protected function success(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()
            ->route($route, $params)
            ->with('status', $message);
    }

    protected function backWithStatus(string $message): RedirectResponse
    {
        return back()->with('status', $message);
    }

    /**
     * Aksi tidak fatally salah, tapi user perlu tahu: berkas tidak ada,
     * data sudah diubah orang lain, dan sejenisnya.
     *
     * @param  array<string, mixed>  $params
     */
    protected function warning(string $route, string $message, array $params = []): RedirectResponse
    {
        return redirect()
            ->route($route, $params)
            ->with('status', $message);
    }
}
