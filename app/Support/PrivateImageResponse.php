<?php
declare(strict_types=1);

namespace App\Support;

/** HTTP cache helpers for authenticated image proxy endpoints. */
final class PrivateImageResponse
{
    /**
     * Send a private cache validator and return true when the browser's copy
     * is current. Call only after authenticating and authorizing the resource.
     */
    public static function isNotModified(string $bucket, string $path): bool
    {
        $etag = self::etag($bucket, $path);
        self::cacheHeaders($etag);

        $header = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if (!is_string($header) || $header === '') {
            return false;
        }

        foreach (explode(',', $header) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '*') {
                http_response_code(304);
                return true;
            }
            // If-None-Match uses weak comparison for GET/HEAD requests.
            if (str_starts_with($candidate, 'W/')) {
                $candidate = substr($candidate, 2);
            }
            if (hash_equals($etag, $candidate)) {
                http_response_code(304);
                return true;
            }
        }

        return false;
    }

    public static function send(string $bytes, string $mime, string $bucket, string $path): void
    {
        self::cacheHeaders(self::etag($bucket, $path));
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
    }

    private static function etag(string $bucket, string $path): string
    {
        // Uploads use immutable, randomized paths; a replacement gets a new tag.
        return '"' . hash('sha256', $bucket . "\0" . $path) . '"';
    }

    private static function cacheHeaders(string $etag): void
    {
        header('Cache-Control: private, no-cache, must-revalidate');
        header('ETag: ' . $etag);
    }
}
