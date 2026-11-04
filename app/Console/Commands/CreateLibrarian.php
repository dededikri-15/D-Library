<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateLibrarian extends Command
{
    protected $signature = 'library:create-librarian';

    protected $description = 'Buat akun pustakawan pertama dengan aman melalui terminal lokal';

    public function handle(): int
    {
        if (User::pustakawan()->exists()) {
            $this->components->error('Akun pustakawan sudah ada. Pustakawan berikutnya dibuat melalui menu Kelola Pengguna.');

            return self::FAILURE;
        }

        $name = $this->ask('Nama lengkap pustakawan');
        $email = Str::lower((string) $this->ask('Email'));
        $password = $this->secret('Password (input tidak ditampilkan)');
        $passwordConfirmation = $this->secret('Ulangi password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $data = $validator->validated();

        $user = Cache::lock('registration:librarian-bootstrap', 30)->block(10, function () use ($data) {
            return DB::transaction(function () use ($data) {
                if (User::pustakawan()->exists()) {
                    return null;
                }

                return User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => User::ROLE_PUSTAKAWAN,
                    'email_verified_at' => now(),
                ]);
            });
        });

        if ($user === null) {
            $this->components->error('Akun pustakawan sudah dibuat oleh proses lain. Tidak ada akun baru yang ditambahkan.');

            return self::FAILURE;
        }

        $this->components->info("Akun pustakawan {$user->email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
