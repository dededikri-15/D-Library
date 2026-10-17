<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublisherRequest;
use App\Models\Publisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublisherController extends Controller
{
    public function index(Request $request): View
    {
        return $this->masterIndex($request, Publisher::class, 'publishers.index', 'publishers');
    }

    public function create(): View
    {
        return view('publishers.create', ['publisher' => new Publisher]);
    }

    public function store(PublisherRequest $request): RedirectResponse
    {
        $publisher = Publisher::create($request->validated());

        $this->notifySelf(
            $request->user(),
            'publisher_created',
            ['subject' => $publisher->name],
            route('publishers.index'),
        );

        return $this->success('publishers.index', __('messages.publisher_created'));
    }

    public function edit(Publisher $publisher): View
    {
        // `books_count` dipakai view untuk menonaktifkan tombol hapus, supaya
        // user tahu sebelum menekan, bukan setelah ditolak.
        $publisher->loadCount('books');

        return view('publishers.edit', ['publisher' => $publisher]);
    }

    public function update(PublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        $publisher->update($request->validated());

        if (collect($publisher->getChanges())->except('updated_at')->isNotEmpty()) {
            $this->notifySelf(
                $request->user(),
                'publisher_updated',
                ['subject' => $publisher->name],
                route('publishers.index'),
            );
        }

        return $this->success('publishers.index', __('messages.publisher_updated'));
    }

    public function destroy(Request $request, Publisher $publisher): RedirectResponse
    {
        $response = $this->destroyMasterData($publisher, 'penerbit', 'publishers.index');

        $this->notifySelf(
            $request->user(),
            'publisher_deleted',
            ['subject' => $publisher->name],
            route('publishers.index'),
        );

        return $response;
    }

    public function storeQuick(Request $request): JsonResponse
    {
        return $this->storeQuickRow(
            $request,
            fn (string $name) => Publisher::create(['name' => $name]),
            'nama penerbit',
            route('publishers.index'),
            uniqueInTable: 'publishers',
        );
    }
}
