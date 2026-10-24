@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="full-width-wrapper">
        <div class="max-w-md w-full space-y-8 bg-white rounded-lg p-8 sm:p-10 lg:p-12">
            <div>
                <h2 class="mt-6 text-3xl font-bold text-gray-900 sm:text-4xl">
                    {{ $hasLibrarian ? 'Setup Pustakawan' : 'Daftar sebagai Pustakawan' }}
                </h2>
            </div>

            @if ($hasLibrarian)
                <p class="mt-4 text-sm text-gray-600">
                    Sudah ada akun pustakawan di sistem. Fitur registrasi pustakawan
                    sekarang tidak aktif.
                </p>
            @elseif (! $registrationEnabled)
                <p class="mt-4 text-sm text-gray-600">
                    Registrasi saat ini tidak aktif. Hubungi administrator untuk
                    mendapatkan akses.
                </p>
            @else
                <p class="mt-4 text-sm text-gray-600">
                    Buat akun pustakawan pertama. Anda memerlukan <strong>kode rahasia</strong>
                    untuk melanjutkan.
                </p>
            @endif
        </div>

        <div class="mt-6 max-w-md w-full">
            @if ($hasLibrarian || ! $registrationEnabled)
                <!-- Tidak ada form -->
            @else
                <div>
                    @if (session('status'))
                        <div class="bg-green-100 border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('librarian.register.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Nama Lengkap
                            </label>
                            <input type="text" name="name" id="name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Masukkan nama lengkap"
                                value="{{ old('name') }}">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email
                            </label>
                            <input type="email" name="email" id="email" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="misal@perpustakaan.test"
                                value="{{ old('email') }}"
                                autocomplete="email">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                Kata Sandi
                            </label>
                            <input type="password" name="password" id="password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Minimal 8 karakter"
                                autocomplete="new-password">
                        </div>

                        <div>
                            <label for="secret" class="block text-sm font-medium text-gray-700 mb-1">
                                Kode Rahasia
                            </label>
                            <input type="text" name="secret" id="secret" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Masukkan kode rahasia"
                                autocomplete="off">
                            <p class="mt-2 text-xs text-gray-500">
                                Kode rahasia untuk mendaftar sebagai pustakawan pertama.
                            </p>
                        </div>

                        <div>
                            <button type="submit"
                                class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Daftar sebagai Pustakawan
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection