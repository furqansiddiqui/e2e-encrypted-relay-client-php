<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay;

use FurqanSiddiqui\E2ESecureRelay\Exception\RelayCurlException;

/**
 * Class RelayCurlResponse
 * @package FurqanSiddiqui\E2ESecureRelay
 */
class RelayCurlResponse
{
    /** @var int */
    public readonly int $statusCode;
    /** @var string|null */
    public readonly ?string $contentType;
    /** @var array */
    public readonly array $headers;
    /** @var string */
    public readonly string $body;

    /**
     * @param \CurlHandle $ch
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\RelayCurlException
     */
    public function __construct(\CurlHandle $ch)
    {
        $headers = [];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use ($headers) {
            if (preg_match('/^[\w\-]+:/', $line)) {
                $header = explode(':', $line, 2);
                $name = trim(strval($header[0] ?? null));
                $value = trim(strval($header[1] ?? null));
                if ($name && $value) {
                    /** @noinspection PhpArrayUsedOnlyForWriteInspection */
                    $headers[strtolower($name)] = $value;
                }
            }

            return strlen($line);
        });

        $body = curl_exec($ch);
        if ($body === false) {
            throw RelayCurlException::CurlError($ch);
        }

        $this->statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $this->headers = $headers;
        $this->body = $body;
        curl_close($ch);
    }
}
