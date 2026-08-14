<?php

namespace App\Exceptions;

use Exception;

class ThreadsApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int|string|null $errorCode = null,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Whether the error indicates the access token is invalid or expired.
     */
    public function isTokenInvalid(): bool
    {
        return $this->httpStatus === 401
            || $this->errorCode === 190
            || str_contains(strtolower($this->message), 'token')
            || str_contains(strtolower($this->message), 'oauth');
    }

    /**
     * Whether the error indicates a rate limit was reached.
     */
    public function isRateLimited(): bool
    {
        return $this->httpStatus === 429
            || $this->errorCode === 4
            || $this->errorCode === 17
            || $this->errorCode === 80004
            || str_contains(strtolower($this->message), 'rate limit')
            || str_contains(strtolower($this->message), 'limit');
    }
}
