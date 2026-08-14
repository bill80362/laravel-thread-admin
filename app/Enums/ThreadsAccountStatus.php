<?php

namespace App\Enums;

enum ThreadsAccountStatus: string
{
    case Active = 'active';
    case NeedsReauth = 'needs_reauth';
    case Disabled = 'disabled';
}
