<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay\Client;

use FurqanSiddiqui\E2ESecureRelay\AbstractE2ERelayNode;
use FurqanSiddiqui\E2ESecureRelay\Exception\RelayClientException;
use FurqanSiddiqui\E2ESecureRelay\Exception\RelayCurlException;
use FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest;
use FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse;

/**
 * Class E2ERelayClient
 * @package FurqanSiddiqui\E2ESecureRelay\Client
 */
class E2ERelayClient extends AbstractE2ERelayNode
{
    /** @var int */
    public int $timeOut = 10;
    /** @var int */
    public int $connectTimeout = 10;

    /**
     * @param string $ipAddress
     * @param int $port
     * @param string $sharedSecret
     */
    public function __construct(
        public readonly string $ipAddress,
        public readonly int    $port,
        string                 $sharedSecret,
    )
    {
        parent::__construct($sharedSecret);
    }

    /**
     * @return bool
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\E2ESecureRelayException
     */
    public function ping(): bool
    {
        return $this->contactNode(null) === 204;
    }

    /**
     * @return bool
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\E2ESecureRelayException
     */
    public function handshake(): bool
    {
        return $this->contactNode($this->encrypt(new RelayCurlRequest("handshake", ""))) === 202;
    }

    /**
     * @param string $method
     * @param string $url
     * @return \FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest
     */
    public function request(string $method, string $url): RelayCurlRequest
    {
        return new RelayCurlRequest($method, $url);
    }

    /**
     * @param \FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest $req
     * @return \FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\E2ESecureRelayException
     */
    public function send(RelayCurlRequest $req): RelayCurlResponse
    {
        return $this->contactNode($this->encrypt($req));
    }

    /**
     * @param string|null $encryptedRequest
     * @return int|\FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\E2ESecureRelayException
     */
    private function contactNode(?string $encryptedRequest): int|RelayCurlResponse
    {
        $ch = curl_init("http://" . $this->ipAddress . ":" . $this->port);
        if (isset($encryptedRequest)) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($encryptedRequest));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $this->defaultUserAgent);
        if ($this->timeOut > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
        }

        if ($this->connectTimeout > 0) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if (false === $response) {
            throw RelayCurlException::CurlError($ch);
        }

        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (in_array($statusCode, [202, 204])) {
            return $statusCode;
        }

        if ($statusCode === 250) {
            return $this->decrypt(base64_decode($response));
        }

        $ex = new RelayClientException("E2E encrypted proxy relay request failed");
        $ex->httpStatusCode = $statusCode;
        if (in_array($statusCode, [452, 453])) {
            $response = explode("\t", $response);
            $ex->errorCode = (int)$response[0] ?? 0;
            $ex->errorMessage = $response[1] ?? null;
        }

        throw $ex;
    }
}
