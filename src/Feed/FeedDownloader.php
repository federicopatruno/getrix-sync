<?php

declare(strict_types=1);

namespace GetrixSync\Feed;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GetrixSync\Support\Config;
use RuntimeException;

final class FeedDownloader
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => (float) Config::get('feed.timeout', 60),
            'connect_timeout' => (float) Config::get(
                'feed.connect_timeout',
                15
            ),
            'http_errors' => true,
            'allow_redirects' => true,
            'headers' => [
                'Accept' => 'application/xml, text/xml;q=0.9, */*;q=0.8',
                'User-Agent' => 'GetrixSync/0.1.0',
            ],
        ]);
    }

    /**
     * Download the configured XML feed.
     */
    public function download(): string
    {
        $url = (string) Config::get('feed.url', '');

        if ($url === '') {
            throw new RuntimeException(
                'Getrix feed URL is not configured.'
            );
        }

        return $this->downloadFrom($url);
    }

    /**
     * Download an XML feed from a specific URL.
     */
    public function downloadFrom(string $url): string
    {
        try {
            $response = $this->client->get($url);
        } catch (GuzzleException $exception) {
            throw new RuntimeException(
                sprintf(
                    'Unable to download Getrix feed: %s',
                    $exception->getMessage()
                ),
                (int) $exception->getCode(),
                $exception
            );
        }

        $body = (string) $response->getBody();

        if ($body === '') {
            throw new RuntimeException(
                'Getrix feed returned an empty response.'
            );
        }

        return $body;
    }
}
