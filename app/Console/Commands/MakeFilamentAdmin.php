<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeFilamentAdmin extends Command
{
    protected $signature = 'make:filament-admin';

    protected $description = '建立 Filament 管理員帳號';

    public function handle(): int
    {
        $data['name'] = text(
            label: 'Name',
            required: true,
        );

        $data['email'] = text(
            label: 'Email',
            required: true,
            validate: fn (string $email): ?string => match (true) {
                Admin::where('email', $email)->exists() => '此 Email 已被使用',
                default => null,
            },
        );

        $data['password'] = password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                strlen($value) < 8 => '密碼至少需要 8 個字元',
                default => null,
            },
        );

        Admin::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->info("管理員 [{$data['email']}] 建立成功！");

        return self::SUCCESS;
    }
}
