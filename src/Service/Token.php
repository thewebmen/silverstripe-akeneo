<?php

namespace WeDevelop\Akeneo\Service;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

class Token
{
    public function __construct(
        private readonly string $accessToken,
        private readonly int    $expiresIn,
        private readonly string $refreshToken
    ) {
    }

    public static function createFromResponse(ResponseInterface $response): self
    {
        try {
            $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            Injector::inst()->get(LoggerInterface::class)->error($exception->getMessage());
            throw $exception;
        }

        return new self(
            $responseData['access_token'],
            $responseData['expires_in'],
            $responseData['refresh_token'],
        );
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
