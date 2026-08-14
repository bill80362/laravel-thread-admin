<?php

namespace App\Enums;

enum ReplyStatus: string
{
    case New = 'new';
    case Replied = 'replied';
    case Ignored = 'ignored';
}
