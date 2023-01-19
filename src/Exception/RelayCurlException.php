<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay\Exception;

/**
 * Class RelayCurlException
 * @package FurqanSiddiqui\E2ESecureRelay\Exception
 */
class RelayCurlException extends E2ESecureRelayException
{
    /** @var \CurlHandle|null */
    public ?\CurlHandle $curlHandle = null;

    /**
     * @param \CurlHandle $ch
     * @return static
     */
    public static function CurlError(\CurlHandle $ch): static
    {
        $ex = new static();
        $ex->curlHandle = $ch;
        return $ex;
    }
}
