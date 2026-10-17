<?php

namespace Tests\Concerns;

/**
 * Penanda permintaan seperti yang dikirim `window.ajax()` (header AJAX +
 * mau JSON) — dipakai bersama oleh kelas hasil pemecahan AjaxFormTest.
 */
trait SendsAjaxRequests
{
    private function asAjax(): self
    {
        return $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);
    }
}
