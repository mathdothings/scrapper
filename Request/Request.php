<?php

namespace App\Request;

use CurlHandle;
use tidy;

/**
 * A cURL-based HTTP request handler with cookie persistence and random user-agent rotation.
 *
 * This class provides a convenient wrapper around PHP's cURL functions with:
 * - Cookie persistence between requests
 * - Automatic random user-agent selection
 * - Guaranteed UTF-8 response encoding
 * - Built-in error handling and logging
 * - HTTP/2 support by default
 * - Configurable timeouts and redirect following
 * - Default headers mimicking browser behavior
 *
 * Example usage:
 * $request = new Request();
 * $response = $request->attempt('https://example.com');
 *
 * @package App\Request
 */
class Request
{
    /** 
     * @var CurlHandle The cURL handler resource 
     */
    private CurlHandle $handler;

    /** 
     * @var array List of browser user agents for random selection 
     */
    private array $browsers;

    /** 
     * @var string Path to the cookie file for persistent cookies 
     */
    private string $cookieFile;

    /**
     * Default cURL options applied to all requests
     * 
     * @var array{
     *     CURLOPT_ENCODING: string,
     *     CURLOPT_FOLLOWLOCATION: bool,
     *     CURLOPT_RETURNTRANSFER: bool,
     *     CURLOPT_HTTP_VERSION: int
     * }
     */
    private const DEFAULT_OPTIONS = [
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
    ];

    /**
     * Initializes a new cURL request handler.
     *
     * @param string $cookieFile Path to cookie file (default: 'Request/Cookies/cookies.txt')
     * @param int $timeout Maximum execution time in seconds (default: 30)
     * @param int $connectTimeout Connection timeout in seconds (default: 10)
     * @param int $maxRedirects Maximum number of redirects to follow (default: 10)
     * @param string $referer Default referer URL (default: 'https://www.google.com/')
     */
    public function __construct(
        string $cookieFile = 'Request/Cookies/cookies.txt',
        int $timeout = 60,
        int $connectTimeout = 10,
        int $maxRedirects = 10,
        string $referer = 'https://www.google.com/'
    ) {
        $this->browsers = require __DIR__ . '/browsers.php';
        $this->cookieFile = $cookieFile;

        $this->handler = curl_init();

        $this->setOptions([
            CURLOPT_USERAGENT => $this->useRandomUserAgent(),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_MAXREDIRS => $maxRedirects,
            CURLOPT_REFERER => $referer,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: pt-BR,en;q=0.5',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
            ],
        ]);
    }

    /**
     * Attempts to fetch the content from the given URL using cURL.
     *
     * @param string $url The URL to fetch content from
     * @return string|false Returns the response content as a UTF-8 encoded string on success,
     *                      or false on failure. Failure can occur due to:
     *                      - cURL execution error
     *                      - Non-HTTP 200 response status
     * @throws \RuntimeException If there's a cURL error (handled internally by handleError())
     */
    public function attempt(string $url): string|false
    {
        curl_setopt($this->handler, CURLOPT_URL, $url);

        $response = curl_exec($this->handler);

        if ($response === false) {
            $this->handleError();
            return false;
        }

        $response = $this->ensureUTF8Enconding($response);

        if (!$this->isHttpOKResponse()) {
            return false;
        }

        return $response;
    }

    /**
     * Destructor - ensures the cURL handler is properly closed.
     */
    public function __destruct()
    {
        if (isset($this->handler)) {
            curl_close($this->handler);
        }
    }

    /**
     * Merges and applies options to the cURL handler.
     *
     * @param array $options Additional cURL options to merge with defaults
     */
    private function setOptions(array $options): void
    {
        curl_setopt_array($this->handler, $options + self::DEFAULT_OPTIONS);
    }

    /**
     * Selects a random user agent from the available browsers list.
     *
     * @return string Randomly selected user agent string
     */
    private function useRandomUserAgent(): string
    {
        return $this->browsers[array_rand($this->browsers)];
    }

    /**
     * Ensures the response is UTF-8 encoded.
     *
     * @param string $response The response to convert
     * @return string UTF-8 encoded response
     */
    private function ensureUTF8Enconding(string $response): string
    {
        $encoding = mb_detect_encoding($response, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        return mb_convert_encoding($response, 'UTF-8', $encoding);
    }

    /**
     * Checks if the last request returned HTTP 200 OK status.
     *
     * @return bool True if HTTP status is 200, false otherwise
     */
    private function isHttpOKResponse(): bool
    {
        return curl_getinfo($this->handler, CURLINFO_HTTP_CODE) === 200;
    }

    /**
     * Handles cURL errors by logging them to the error log.
     */
    private function handleError(): void
    {
        error_log('Curl error: ' . curl_error($this->handler));
    }
}
