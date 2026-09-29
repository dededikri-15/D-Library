<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithFlash;
use App\Http\Requests\PublisherRequest;
use App\Models\Publisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublisherController extends Controller
{
    use RespondsWithFlash;

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

        return $this->success('publishers.index', 'Penerbit berhasil ditambahkan.');
    }

    public function edit(Publisher $publisher): View
    {
        return view('publishers.edit', ['publisher' => $publisher]);
    }

    public function update(PublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        $publisher->update($request->validated());

        return $this->success('publishers.index', 'Penerbit berhasil diperbarui.');
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        $publisher->delete();

        return $this->success('publishers.index', 'Penerbit berhasil dihapus.');
    }
}
