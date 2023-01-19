<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay;

/**
 * Class RelayCurlRequest
 * @package FurqanSiddiqui\E2ESecureRelay
 */
class RelayCurlRequest
{
    /** @var array */
    public array $headers = [];
    /** @var string */
    public string $body = "";
    /** @var string|null */
    public ?string $userAgent = null;
    /** @var int|null */
    public ?int $httpVersion = null;
    /** @var int */
    public int $timeOut = 10;
    /** @var int */
    public int $connectTimeout = 10;
    /** @var bool */
    public bool $useSSL;
    /** @var bool */
    public bool $sslVerifyPeer = true;
    /** @var bool */
    public bool $sslVerifyHost = true;

    /**
     * @param string $method
     * @param string $url
     */
    public function __construct(public string $method, public string $url)
    {
        $this->useSSL = strtolower(substr($this->url, 0, 4)) === "https";
    }
}
