<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReplyStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Publishing = 'publishing';
    case Replied = 'replied';
    case Failed = 'failed';
    case Ignored = 'ignored';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::New => '待處理',
            self::Publishing => '發佈中',
            self::Replied => '已回覆',
            self::Failed => '發佈失敗',
            self::Ignored => '已忽略',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::New => 'warning',
            self::Publishing => 'info',
            self::Replied => 'success',
            self::Failed => 'danger',
            self::Ignored => 'gray',
        };
    }
}
