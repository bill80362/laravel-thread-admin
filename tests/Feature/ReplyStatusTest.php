<?php

namespace Tests\Feature;

use App\Enums\ReplyStatus;
use Tests\TestCase;

class ReplyStatusTest extends TestCase
{
    public function test_has_publishing_and_failed_cases(): void
    {
        $this->assertSame('publishing', ReplyStatus::Publishing->value);
        $this->assertSame('failed', ReplyStatus::Failed->value);
    }

    public function test_labels(): void
    {
        $this->assertSame('待處理', ReplyStatus::New->getLabel());
        $this->assertSame('發佈中', ReplyStatus::Publishing->getLabel());
        $this->assertSame('已回覆', ReplyStatus::Replied->getLabel());
        $this->assertSame('發佈失敗', ReplyStatus::Failed->getLabel());
        $this->assertSame('已忽略', ReplyStatus::Ignored->getLabel());
    }

    public function test_colors(): void
    {
        $this->assertSame('warning', ReplyStatus::New->getColor());
        $this->assertSame('info', ReplyStatus::Publishing->getColor());
        $this->assertSame('success', ReplyStatus::Replied->getColor());
        $this->assertSame('danger', ReplyStatus::Failed->getColor());
        $this->assertSame('gray', ReplyStatus::Ignored->getColor());
    }
}
