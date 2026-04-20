<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

final class BiscordHttpClient
{
    private string $baseUrl;

    /**
     * @var array<string,string>
     */
    private array $cookies = [];

    private ?int $lastResponseStatus = null;
    private ?string $lastRequestUrl = null;

    public function __construct(?string $baseUrl = null)
    {
        $resolvedBaseUrl = $baseUrl ?? (string) getenv('CONTRACT_TEST_BASE_URL');
        if ($resolvedBaseUrl === '') {
            $resolvedBaseUrl = 'http://127.0.0.1:8000';
        }

        $this->baseUrl = $this->normalizeBaseUrl($resolvedBaseUrl);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHost(): string
    {
        return (string) parse_url($this->baseUrl, PHP_URL_HOST);
    }

    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    public function getCookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function hasCookie(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * @return array<string,string>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getLastResponseStatus(): ?int
    {
        return $this->lastResponseStatus;
    }

    public function getLastRequestUrl(): ?string
    {
        return $this->lastRequestUrl;
    }

    /**
     * @param array<string, scalar|null> $payload
     * @return array{status:int,headers:array<int,string>,body:string,json:array<string,mixed>|null}
     */
    public function postJson(string $path, array $payload): array
    {
        return $this->request('POST', $path, [
            'Content-Type: application/json',
            'Accept: application/json',
        ], json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param array<string, scalar|null> $payload
     * @return array{status:int,headers:array<int,string>,body:string,json:array<string,mixed>|null}
     */
    public function postForm(string $path, array $payload): array
    {
        return $this->request('POST', $path, [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], http_build_query($payload));
    }

    /**
     * @return array{status:int,headers:array<int,string>,body:string,json:array<string,mixed>|null}
     */
    public function get(string $path): array
    {
        return $this->request('GET', $path, ['Accept: application/json'], null);
    }

    /**
     * @param list<string> $headers
     * @return array{status:int,headers:array<int,string>,body:string,json:array<string,mixed>|null}
     */
    public function request(string $method, string $path, array $headers = [], ?string $body = null): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $this->lastRequestUrl = $url;
        $responseHeaders = [];

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
                $responseHeaders[] = trim($line);
                return strlen($line);
            },
            CURLOPT_HTTPHEADER => $this->buildHeaders($headers),
            CURLOPT_TIMEOUT => 10,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $rawBody = curl_exec($curl);

        if ($rawBody === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \RuntimeException('HTTP request failed: ' . $error);
        }

        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $this->lastResponseStatus = $status;

        curl_close($curl);

        $this->captureSetCookieHeaders($responseHeaders);

        $json = json_decode($rawBody, true);
        if (!is_array($json)) {
            $json = null;
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => $rawBody,
            'json' => $json,
        ];
    }

    /**
     * @param list<string> $headers
     * @return list<string>
     */
    private function buildHeaders(array $headers): array
    {
        $built = $headers;

        if ($this->cookies !== []) {
            $cookieParts = [];
            foreach ($this->cookies as $name => $value) {
                $cookieParts[] = $name . '=' . $value;
            }

            $built[] = 'Cookie: ' . implode('; ', $cookieParts);
        }

        return $built;
    }

    /**
     * @param list<string> $responseHeaders
     */
    private function captureSetCookieHeaders(array $responseHeaders): void
    {
        foreach ($responseHeaders as $headerLine) {
            if (stripos($headerLine, 'Set-Cookie:') !== 0) {
                continue;
            }

            $raw = trim(substr($headerLine, strlen('Set-Cookie:')));
            $cookiePair = explode(';', $raw, 2)[0] ?? '';
            [$name, $value] = array_pad(explode('=', $cookiePair, 2), 2, null);

            if ($name === null || $value === null || $name === '') {
                continue;
            }

            $this->cookies[$name] = $value;
        }
    }

    private function normalizeBaseUrl(string $baseUrl): string
    {
        $normalized = rtrim($baseUrl, '/');
        $parts = parse_url($normalized);

        if (!is_array($parts)) {
            throw new \InvalidArgumentException('Invalid CONTRACT_TEST_BASE_URL: ' . $baseUrl);
        }

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '127.0.0.1';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }

        return sprintf('%s://%s%s', $scheme, $host, $port);
    }
}
