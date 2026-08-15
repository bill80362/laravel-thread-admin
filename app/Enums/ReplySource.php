<?php

namespace App\Enums;

enum ReplySource: string
{
    case Polling = 'polling';
    case Webhook = 'webhook';
    case Manual = 'manual';
}
