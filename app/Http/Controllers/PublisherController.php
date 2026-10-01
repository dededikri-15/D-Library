<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublisherRequest;
use App\Models\Publisher;
use App\Support\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        Publisher::create($request->validated());

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

        return $this->success('publishers.index', __('messages.publisher_updated'));
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        return $this->destroyMasterData($publisher, 'penerbit', 'publishers.index');
    }

    public function storeQuick(Request $request): JsonResponse
    {
        // Validasi inline tetap perlu `attributes` sendiri: tanpa itu pesan
        // errornya menyebut field mentah "name", bukan "nama penerbit".
        $validated = $request->validate(
            [
                'name' => [
                    'required', 'string', 'max:150',
                    Rule::unique('publishers', 'name'),
                ],
            ],
            attributes: ['name' => 'nama penerbit'],
        );

        $publisher = Publisher::create(['name' => $validated['name']]);

        return Json::response([
            'id' => $publisher->id,
            'name' => $publisher->name,
        ]);
    }
}
