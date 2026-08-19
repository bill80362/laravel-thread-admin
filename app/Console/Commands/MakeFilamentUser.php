<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class MakeFilamentUser extends Command
{
    protected $signature = 'make:filament-user';

    protected $description = '建立 Filament 使用者帳號（含控管設定）';

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
                User::where('email', $email)->exists() => '此 Email 已被使用',
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

        $data['max_accounts'] = (int) text(
            label: '最大綁定帳號數',
            default: '3',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                ! is_numeric($value) || (int) $value < 0 => '請輸入正整數',
                default => null,
            },
        );

        $data['max_daily_posts'] = (int) text(
            label: '每日發文上限',
            default: '10',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                ! is_numeric($value) || (int) $value < 0 => '請輸入正整數',
                default => null,
            },
        );

        $data['max_daily_replies'] = (int) text(
            label: '每日回覆上限',
            default: '50',
            required: true,
            validate: fn (string $value): ?string => match (true) {
                ! is_numeric($value) || (int) $value < 0 => '請輸入正整數',
                default => null,
            },
        );

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'max_accounts' => $data['max_accounts'],
            'max_daily_posts' => $data['max_daily_posts'],
            'max_daily_replies' => $data['max_daily_replies'],
            'is_active' => true,
        ]);

        $this->info("使用者 [{$data['email']}] 建立成功！");

        return self::SUCCESS;
    }
}
