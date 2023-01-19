<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay;

use FurqanSiddiqui\E2ESecureRelay\Exception\CipherException;

/**
 * Class AbstractE2ERelayNode
 * @package FurqanSiddiqui\E2ESecureRelay
 */
abstract class AbstractE2ERelayNode
{
    /** @var string */
    public const VERSION = "1.0.0";

    /** @var string */
    protected readonly string $sharedSecret;
    /** @var int */
    protected readonly int $cipherIVLength;
    /** @var string */
    protected readonly string $defaultUserAgent;

    /**
     * @param string $sharedSecret
     */
    public function __construct(string $sharedSecret)
    {
        $this->sharedSecret = hash("sha256", $sharedSecret, true);
        $this->cipherIVLength = openssl_cipher_iv_length("AES-256-CBC");
        $this->defaultUserAgent = "E2E-Encrypted-Relay/" . static::VERSION .
            " (+https://github.com/furqansiddiqui/e2e-encrypted-relay-client-php)";
    }

    /**
     * @param \FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest|\FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse $req
     * @return string
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\CipherException
     */
    protected function encrypt(RelayCurlRequest|RelayCurlResponse $req): string
    {
        try {
            $iv = random_bytes($this->cipherIVLength);
        } catch (\Exception) {
            throw new CipherException('Failed to generate PRNG bytes for IV');
        }

        $encrypted = openssl_encrypt(serialize(clone $req), "AES-256-CBC", $this->sharedSecret, OPENSSL_RAW_DATA, $iv);
        if (!$encrypted) {
            throw new CipherException('Failed to encrypt E2E-secure-relay node data');
        }

        return $iv . $encrypted;
    }

    /**
     * @param string $raw
     * @return \FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest|\FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\CipherException
     */
    protected function decrypt(string $raw): RelayCurlRequest|RelayCurlResponse
    {
        $decrypted = openssl_decrypt(
            substr($raw, $this->cipherIVLength),
            "AES-256-CBC", $this->sharedSecret,
            OPENSSL_RAW_DATA,
            substr($raw, 0, $this->cipherIVLength)
        );

        if (!$decrypted) {
            throw new CipherException('Failed to decrypt E2E-secure-relay-node data');
        }

        $object = unserialize($decrypted, [
            "allowed_classes" => [
                'FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest',
                'FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse',
            ],
        ]);

        if (!$object instanceof RelayCurlRequest && !$object instanceof RelayCurlResponse) {
            throw new CipherException(
                sprintf('Bad encrypted data of type "%s" in E2E-secure-relay-node', is_object($object) ? get_class($object) : gettype($object))
            );
        }

        return $object;
    }
}
