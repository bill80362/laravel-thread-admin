<?php

namespace App\Services;

use App\Exceptions\ThreadsApiException;
use App\Models\ThreadsAccount;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * Thin wrapper around the Threads Graph API.
 *
 * @see https://developers.facebook.com/docs/threads
 */
class ThreadsClient
{
    private const API_BASE = 'https://graph.threads.net/v1.0';

    private const OAUTH_BASE = 'https://graph.threads.net';

    /**
     * Scopes requested during the OAuth authorization window.
     */
    public const SCOPES = [
        'threads_basic',
        'threads_content_publish',
        'threads_manage_replies',
        'threads_read_replies',
    ];

    public function __construct(
        private readonly ClientInterface $http,
    ) {}

    /**
     * Build the Threads OAuth authorization URL.
     */
    public function buildAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => Config::get('services.threads.client_id'),
            'redirect_uri' => Config::get('services.threads.redirect_uri'),
            'scope' => implode(',', self::SCOPES),
            'response_type' => 'code',
            'state' => $state,
        ]);

        return 'https://www.threads.net/oauth/authorize?'.$query;
    }

    /**
     * Exchange an authorization code for a short-lived access token.
     */
    public function exchangeCodeForShortToken(string $code): string
    {
        $data = $this->request('POST', '/oauth/access_token', [
            'client_id' => Config::get('services.threads.client_id'),
            'client_secret' => Config::get('services.threads.client_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => Config::get('services.threads.redirect_uri'),
            'code' => $code,
        ], self::OAUTH_BASE);

        return $data['access_token'];
    }

    /**
     * Exchange a short-lived token for a long-lived token.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function exchangeShortForLongToken(string $shortToken): array
    {
        return $this->request('GET', '/access_token', [
            'grant_type' => 'th_exchange_token',
            'client_secret' => Config::get('services.threads.client_secret'),
            'access_token' => $shortToken,
        ], self::OAUTH_BASE);
    }

    /**
     * Refresh a long-lived token before it expires.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function refreshLongLivedToken(string $token): array
    {
        return $this->request('GET', '/refresh_access_token', [
            'grant_type' => 'th_refresh_token',
            'access_token' => $token,
        ], self::OAUTH_BASE);
    }

    /**
     * Retrieve the authenticated user's Threads profile.
     *
     * @return array{id: string, username: string, name?: string}
     */
    public function getProfile(string $token): array
    {
        return $this->request('GET', '/me', [
            'fields' => 'id,username,name',
            'access_token' => $token,
        ]);
    }

    /**
     * Create a text media container for a post or reply.
     *
     * @param  string|null  $replyToId  the media ID to reply to, if this is a reply
     */
    public function createTextContainer(ThreadsAccount $account, string $text, ?string $replyToId = null): string
    {
        $params = [
            'media_type' => 'TEXT',
            'text' => $text,
            'access_token' => $account->access_token,
        ];

        if ($replyToId !== null) {
            $params['reply_to_id'] = $replyToId;
        }

        $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

        return $data['id'];
    }

    /**
     * Create an image media container for a post.
     *
     * @param  string|null  $text  optional caption text
     */
    public function createImageContainer(ThreadsAccount $account, string $imageUrl, ?string $text = null): string
    {
        $params = [
            'media_type' => 'IMAGE',
            'image_url' => $imageUrl,
            'access_token' => $account->access_token,
        ];

        if ($text !== null && $text !== '') {
            $params['text'] = $text;
        }

        $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

        return $data['id'];
    }

    /**
     * Create a carousel item media container (is_carousel_item=true).
     *
     * @see https://developers.facebook.com/docs/threads/posts#carousel-posts
     */
    public function createCarouselItemContainer(ThreadsAccount $account, string $imageUrl): string
    {
        $params = [
            'media_type' => 'IMAGE',
            'image_url' => $imageUrl,
            'is_carousel_item' => 'true',
            'access_token' => $account->access_token,
        ];

        $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

        return $data['id'];
    }

    /**
     * Create a carousel container that wraps carousel item containers.
     *
     * @param  string[]  $childrenIds
     * @param  string|null  $text  optional caption text
     *
     * @see https://developers.facebook.com/docs/threads/posts#carousel-posts
     */
    public function createCarouselContainer(ThreadsAccount $account, array $childrenIds, ?string $text = null): string
    {
        $params = [
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $childrenIds),
            'access_token' => $account->access_token,
        ];

        if ($text !== null && $text !== '') {
            $params['text'] = $text;
        }

        $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

        return $data['id'];
    }

    /**
     * Publish a media container and return the resulting media ID.
     */
    public function publishContainer(ThreadsAccount $account, string $creationId): string
    {
        $data = $this->request('POST', "/{$account->threads_user_id}/threads_publish", [
            'creation_id' => $creationId,
            'access_token' => $account->access_token,
        ]);

        return $data['id'];
    }

    /**
     * Check the publishing status of a media container.
     *
     * @return array{id: string, status?: string, error_message?: string}
     */
    public function getContainerStatus(ThreadsAccount $account, string $creationId): array
    {
        return $this->request('GET', "/{$creationId}", [
            'fields' => 'status,error_message',
            'access_token' => $account->access_token,
        ]);
    }

    /**
     * Retrieve replies for a Threads media object.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReplies(ThreadsAccount $account, string $mediaId): array
    {
        $data = $this->request('GET', "/{$mediaId}/replies", [
            'fields' => 'id,username,text,timestamp',
            'access_token' => $account->access_token,
        ]);

        return $data['data'] ?? [];
    }

    /**
     * Delete a Threads media object.
     *
     * @see https://developers.facebook.com/docs/threads/reference/publishing#delete-a-threads-media-object
     */
    public function deleteMedia(ThreadsAccount $account, string $mediaId): bool
    {
        $this->request('DELETE', "/{$mediaId}", [
            'access_token' => $account->access_token,
        ]);

        return true;
    }

    /**
     * Send a raw request to the Threads API and decode the JSON response.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $params, string $base = self::API_BASE): array
    {
        $options = match ($method) {
            'GET', 'DELETE' => ['query' => $params],
            default => ['form_params' => $params],
        };

        $url = $base.$path;

        $this->logCurl($method, $url, $params);

        try {
            $response = $this->http->request($method, $url, $options);
        } catch (ClientException $e) {
            $this->logResponse($e->getResponse());

            throw $this->toApiException($e);
        } catch (GuzzleException $e) {
            Log::error('Threads API request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            throw new ThreadsApiException($e->getMessage(), null, null);
        }

        return $this->decode($response);
    }

    /**
     * 記錄完整 curl 請求內容（暫時性除錯用）。
     *
     * @param  array<string, mixed>  $params
     */
    private function logCurl(string $method, string $url, array $params): void
    {
        $query = http_build_query($params);

        $curl = match ($method) {
            'GET', 'DELETE' => "curl -X {$method} '{$url}?{$query}'",
            default => "curl -X {$method} '{$url}' -d '{$query}'",
        };

        Log::info('Threads API request', [
            'curl' => $curl,
        ]);
    }

    /**
     * 記錄失敗回應的狀態碼與 body（僅供除錯用）。
     */
    private function logResponse(ResponseInterface $response): void
    {
        Log::info('Threads API error response', [
            'status' => $response->getStatusCode(),
            'body' => (string) $response->getBody(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $body = json_decode((string) $response->getBody(), true);

        if (isset($body['error'])) {
            $error = $body['error'];

            $this->logResponse($response);

            throw new ThreadsApiException(
                $error['message'] ?? 'Threads API error',
                isset($error['code']) ? (int) $error['code'] : null,
                $response->getStatusCode(),
            );
        }

        return is_array($body) ? $body : [];
    }

    private function toApiException(ClientException $e): ThreadsApiException
    {
        $body = json_decode((string) $e->getResponse()->getBody(), true);
        $error = $body['error'] ?? [];

        return new ThreadsApiException(
            $error['message'] ?? $e->getMessage(),
            isset($error['code']) ? (int) $error['code'] : null,
            $e->getResponse()->getStatusCode(),
        );
    }
}
