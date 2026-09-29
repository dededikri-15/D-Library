<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithFlash;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    use RespondsWithFlash;

    public function index(Request $request): View
    {
        $filters = $this->validFilters($request, [
            'q' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', Rule::in(User::roles())],
        ]);

        $users = User::query()
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when($filters['q'] ?? null, fn ($query, $q) => $this->applySearch($query, 'name', $q))
            ->orderBy('name')
            ->paginate(config('perpustakaan.pagination.per_page'))
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => User::roles(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User,
            'roles' => User::roles(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return $this->success('users.index', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => User::roles(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Jangan izinkan pustakawan mengosongkan atau mengganti passwordnya
        // sendiri lewat form edit tanpa mengetik password lama.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->update($data);

        return $this->success('users.index', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Cegah pustakawan menghapus akunnya sendiri (bisa mengunci sistem).
        if ($user->id === $request->user()?->id) {
            return $this->backWithStatus('Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return $this->success('users.index', 'Pengguna berhasil dihapus.');
    }
}
