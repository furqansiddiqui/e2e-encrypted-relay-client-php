<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay\Exception;

/**
 * Class RelayClientException
 * @package FurqanSiddiqui\E2ESecureRelay\Exception
 */
class RelayClientException extends E2ESecureRelayException
{
    /** @var int|null */
    public ?int $httpStatusCode = null;
    /** @var int|null */
    public ?int $errorCode = null;
    /** @var string|null*/
    public ?string $errorMessage = null;
}
