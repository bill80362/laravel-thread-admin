<?php

namespace Tests\Unit;

use App\Exceptions\ThreadsApiException;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ThreadsClientTest extends TestCase
{
    use RefreshDatabase;

    private ThreadsClient $client;

    private ClientInterface&MockInterface $http;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.threads.client_id', 'test-client-id');
        Config::set('services.threads.client_secret', 'test-client-secret');

        $this->http = Mockery::mock(ClientInterface::class);
        $this->client = new ThreadsClient($this->http);
    }

    public function test_exchange_code_for_short_token_returns_token(): void
    {
        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'short-token',
            ])));

        $token = $this->client->exchangeCodeForShortToken('code');

        $this->assertSame('short-token', $token);
    }

    public function test_exchange_short_for_long_token_returns_token_and_expiry(): void
    {
        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'long-token',
                'expires_in' => 5184000,
            ])));

        $result = $this->client->exchangeShortForLongToken('short-token');

        $this->assertSame('long-token', $result['access_token']);
        $this->assertSame(5184000, $result['expires_in']);
    }

    public function test_refresh_long_lived_token_returns_new_token(): void
    {
        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'refreshed-token',
                'expires_in' => 5184000,
            ])));

        $result = $this->client->refreshLongLivedToken('old-token');

        $this->assertSame('refreshed-token', $result['access_token']);
    }

    public function test_get_profile_returns_profile_fields(): void
    {
        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'id' => '12345',
                'username' => 'testuser',
                'name' => 'Test User',
            ])));

        $profile = $this->client->getProfile('token');

        $this->assertSame('12345', $profile['id']);
        $this->assertSame('testuser', $profile['username']);
    }

    public function test_create_text_container_returns_creation_id(): void
    {
        $account = ThreadsAccount::factory()->create();

        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'id' => 'container-id',
            ])));

        $creationId = $this->client->createTextContainer($account, 'hello');

        $this->assertSame('container-id', $creationId);
    }

    public function test_api_error_throws_threads_api_exception(): void
    {
        $account = ThreadsAccount::factory()->create();

        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(400, [], json_encode([
                'error' => [
                    'message' => 'Invalid parameter',
                    'code' => 100,
                ],
            ])));

        $this->expectException(ThreadsApiException::class);

        $this->client->createTextContainer($account, 'hello');
    }

    public function test_client_exception_maps_to_threads_api_exception(): void
    {
        $account = ThreadsAccount::factory()->create();

        $request = new Request('POST', 'https://graph.threads.net/v1.0/test/threads');
        $response = new Response(401, [], json_encode([
            'error' => [
                'message' => 'Invalid OAuth access token',
                'code' => 190,
            ],
        ]));

        $this->http->shouldReceive('request')
            ->once()
            ->andThrow(new ClientException('Client error', $request, $response));

        try {
            $this->client->createTextContainer($account, 'hello');
            $this->fail('Expected ThreadsApiException was not thrown');
        } catch (ThreadsApiException $e) {
            $this->assertTrue($e->isTokenInvalid());
            $this->assertSame(190, $e->errorCode);
        }
    }

    public function test_rate_limit_exception_is_detected(): void
    {
        $account = ThreadsAccount::factory()->create();

        $request = new Request('POST', 'https://graph.threads.net/v1.0/test/threads');
        $response = new Response(429, [], json_encode([
            'error' => [
                'message' => 'Application request limit reached',
                'code' => 4,
            ],
        ]));

        $this->http->shouldReceive('request')
            ->once()
            ->andThrow(new ClientException('Client error', $request, $response));

        try {
            $this->client->createTextContainer($account, 'hello');
            $this->fail('Expected ThreadsApiException was not thrown');
        } catch (ThreadsApiException $e) {
            $this->assertTrue($e->isRateLimited());
        }
    }

    public function test_get_replies_returns_data_array(): void
    {
        $account = ThreadsAccount::factory()->create();

        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'data' => [
                    ['id' => 'reply-1', 'username' => 'user1', 'text' => 'hi'],
                ],
            ])));

        $replies = $this->client->getReplies($account, 'media-id');

        $this->assertCount(1, $replies);
        $this->assertSame('reply-1', $replies[0]['id']);
    }

    public function test_delete_media_returns_true(): void
    {
        $account = ThreadsAccount::factory()->create();

        $this->http->shouldReceive('request')
            ->once()
            ->with('DELETE', 'https://graph.threads.net/v1.0/media-id', Mockery::any())
            ->andReturn(new Response(200, [], json_encode(['success' => true])));

        $result = $this->client->deleteMedia($account, 'media-id');

        $this->assertTrue($result);
    }

    public function test_request_logs_full_curl_content(): void
    {
        $account = ThreadsAccount::factory()->create();

        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode([
                'id' => 'container-id',
            ])));

        $logged = [];

        Log::listen(function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event;
        });

        $this->client->createTextContainer($account, 'hello');

        $curlEvents = array_values(array_filter($logged, fn (MessageLogged $event): bool => $event->message === 'Threads API request'));

        $this->assertCount(1, $curlEvents);
        $this->assertSame('info', $curlEvents[0]->level);
        $this->assertStringContainsString('curl -X POST', $curlEvents[0]->context['curl']);
        $this->assertStringContainsString("/{$account->threads_user_id}/threads", $curlEvents[0]->context['curl']);
        $this->assertStringContainsString('media_type=TEXT', $curlEvents[0]->context['curl']);
    }

    public function test_failed_request_logs_response_status_and_body(): void
    {
        $account = ThreadsAccount::factory()->create();

        $this->http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(400, [], json_encode([
                'error' => [
                    'message' => 'Invalid parameter',
                    'code' => 100,
                ],
            ])));

        $logged = [];

        Log::listen(function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event;
        });

        try {
            $this->client->createTextContainer($account, 'hello');
            $this->fail('Expected ThreadsApiException was not thrown');
        } catch (ThreadsApiException) {
            // 預期例外
        }

        $errorEvents = array_values(array_filter($logged, fn (MessageLogged $event): bool => $event->message === 'Threads API error response'));

        $this->assertCount(1, $errorEvents);
        $this->assertSame(400, $errorEvents[0]->context['status']);
        $this->assertStringContainsString('Invalid parameter', $errorEvents[0]->context['body']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
