<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PostStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Publishing = 'publishing';
    case Published = 'published';
    case Failed = 'failed';
    case Deleting = 'deleting';
    case DeleteFailed = 'delete_failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Scheduled => '排程中',
            self::Publishing => '發佈中',
            self::Published => '已發佈',
            self::Failed => '失敗',
            self::Deleting => '刪除中',
            self::DeleteFailed => '刪除失敗',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'warning',
            self::Publishing => 'info',
            self::Published => 'success',
            self::Failed => 'danger',
            self::Deleting => 'warning',
            self::DeleteFailed => 'danger',
        };
    }
}
