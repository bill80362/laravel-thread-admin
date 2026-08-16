<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\ThreadsAccount;
use App\Services\ReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sets_manual_source_and_new_status(): void
    {
        $account = ThreadsAccount::factory()->create();

        $reply = app(ReplyService::class)->create([
            'threads_account_id' => $account->id,
            'author_username' => 'someuser',
            'text' => '測試回覆',
        ]);

        $this->assertDatabaseHas('replies', [
            'id' => $reply->id,
            'threads_account_id' => $account->id,
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
            'post_id' => null,
        ]);
    }

    public function test_list_filters_by_status(): void
    {
        $service = app(ReplyService::class);
        $service->create([
            'threads_account_id' => ThreadsAccount::factory()->create()->id,
            'author_username' => 'a',
            'text' => 'one',
        ]);
        $service->create([
            'threads_account_id' => ThreadsAccount::factory()->create()->id,
            'author_username' => 'b',
            'text' => 'two',
        ]);

        $result = $service->list(['status' => ReplyStatus::New->value]);

        $this->assertCount(2, $result);
    }
}
