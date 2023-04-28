<?php

namespace WeDevelop\Akeneo\Service;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\SiteConfig\SiteConfig;

class AkeneoApi
{
    private const URI = 'api/rest/v1/';
    private const TOKEN_URI = 'api/oauth/v1/';

    private string $host;
    private string $clientId;
    private string $secret;
    private string $username;
    private string $password;
    private ?string $channel;

    private Client $apiClient;
    private Client $tokenClient;

    private ?Token $token = null;

    public function __construct()
    {
        $siteConfig = SiteConfig::current_site_config();
        $this->host = $siteConfig->AkeneoURL;
        $this->clientId = $siteConfig->AkeneoClientID;
        $this->secret = $siteConfig->AkeneoSecret;
        $this->username = $siteConfig->AkeneoUsername;
        $this->password = $siteConfig->AkeneoPassword;
        $this->channel = $siteConfig->AkeneoChannel;
        $this->tokenClient = new Client(['base_uri' => $this->host . '/' . self::TOKEN_URI]);
        $this->apiClient = new Client(['base_uri' => $this->host . '/' . self::URI]);
    }

    public function authorize(): void
    {
        $this->token = $this->requestToken();
    }

    public function getCategories(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        return $this->request('categories', ['query' => $query]);
        ;
    }

    public function getAttributes(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        return $this->request('attributes', ['query' => $query]);
        ;
    }

    public function getAttributeGroups(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        return $this->request('attribute-groups', ['query' => $query]);
    }

    public function getFamilies(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        return $this->request('families', ['query' => $query]);
    }

    public function getVariants(int $page, int $limit, string $familyCode): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        $uri = sprintf('families/%s/variants', $familyCode);

        return $this->request($uri, ['query' => $query]);
    }

    public function getAttributeOptions(int $page, int $limit, string $attributeCode): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        $uri = sprintf('attributes/%s/options', $attributeCode);

        return $this->request($uri, ['query' => $query]);
    }

    public function getProductModels(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
            'with_attribute_options' => 'true',
        ];

        if ($this->channel) {
            $query['scope'] = $this->channel;
        }

        return $this->request('product-models', ['query' => $query]);
    }

    public function getProducts(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        if ($this->channel) {
            $query['scope'] = $this->channel;
        }

        return $this->request('products', ['query' => $query]);
    }

    public function getMediaFiles(int $page = 1, int $limit = 100): array
    {
        $query = [
            'page' => $page,
            'limit' => $limit,
        ];

        return $this->request('media-files', ['query' => $query]);
    }

    public function getMediaFile(string $code): array
    {
        return $this->request(sprintf('media-files/%s', $code));
    }

    public function downloadMediaFile(string $code): ?string
    {
        return $this->request(sprintf('media-files/%s/download', $code), withCount: false);
    }

    public function getChannels(): array
    {
        return $this->request('channels');
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<mixed>|string
     */
    private function request(string $uri, array $options = [], bool $withCount = true)
    {
        if (!$this->token) {
            $this->authorize();
        }

        $options['headers']['Authorization'] = 'Bearer ' . $this->token->getAccessToken();
        if ($withCount) {
            $options['query']['with_count'] = 'true';
        }

        try {
            $response = $this->apiClient->get($uri, $options);
        } catch (\Exception $e) {
            Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
            throw $e;
        }

        if ($response->getHeader('Content-Type')[0] !== 'application/json') {
            return $response->getBody()->getContents();
        }

        try {
            $responseData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
            throw $e;
        }

        return $responseData;
    }

    private function requestToken(): Token
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $auth = [$this->clientId, $this->secret];
        $body = [
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password,
        ];

        try {
            $response = $this->tokenClient->post('token', [
                'headers' => $headers,
                'auth' => $auth,
                'body' => json_encode($body),
            ]);
        } catch (\Exception $e) {
            Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
            throw $e;
        }

        return Token::createFromResponse($response);
    }

    private function refreshToken(): Token
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $auth = [$this->clientId, $this->secret];
        $body = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->token->getRefreshToken(),
        ];

        try {
            $response = $this->tokenClient->post('token', [
                'headers' => $headers,
                'auth' => $auth,
                'body' => json_encode($body),
            ]);
        } catch (\Exception $e) {
            Injector::inst()->get(LoggerInterface::class)->error($e->getMessage());
            throw $e;
        }

        return Token::createFromResponse($response);
    }
}
