<?php
declare(strict_types=1);

namespace FurqanSiddiqui\E2ESecureRelay\Server;

use FurqanSiddiqui\E2ESecureRelay\AbstractE2ERelayNode;
use FurqanSiddiqui\E2ESecureRelay\Exception\CipherException;
use FurqanSiddiqui\E2ESecureRelay\Exception\RelayCurlException;
use FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest;
use FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse;

/**
 * Class E2ERelayNode
 * @package FurqanSiddiqui\E2ESecureRelay\Server
 */
class E2ERelayNode extends AbstractE2ERelayNode
{
    /** @var array|string[] */
    public readonly array $ipWhitelist;

    /**
     * @param string $sharedSecret
     * @param string $ipWhitelist
     */
    public function __construct(string $sharedSecret, string $ipWhitelist)
    {
        parent::__construct($sharedSecret);
        $this->ipWhitelist = array_values(array_filter(explode(",", trim($ipWhitelist)), function ($ip) {
            if (filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }

            return null;
        }));
    }

    /**
     * @param \FurqanSiddiqui\E2ESecureRelay\RelayCurlRequest $req
     * @return \FurqanSiddiqui\E2ESecureRelay\RelayCurlResponse
     * @throws \FurqanSiddiqui\E2ESecureRelay\Exception\RelayCurlException
     */
    private function relayCurlRequest(RelayCurlRequest $req): RelayCurlResponse
    {
        $req->method = strtolower($req->method);
        if (!in_array($req->method, ["get", "post", "put", "delete", "options"])) {
            throw new RelayCurlException('Invalid HTTP method', 1001);
        }

        $ch = curl_init();
        if (!$req->url || !curl_setopt($ch, CURLOPT_URL, $req->url)) {
            throw new RelayCurlException('Invalid destination URL', 1002);
        }

        if ($req->httpVersion) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, $req->httpVersion);
        }

        if ($req->useSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $req->sslVerifyPeer);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $req->sslVerifyHost ? 2 : 0);

            if ($req->sslVerifyPeer || $req->sslVerifyHost) {
                curl_setopt($ch, CURLOPT_CAPATH, dirname(__DIR__) . DIRECTORY_SEPARATOR . "ssl" . DIRECTORY_SEPARATOR);
            }
        }

        if ($req->method === "get") {
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtolower($req->method));
            if ($req->body) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $req->body);
            } else {
                curl_setopt($ch, CURLOPT_NOBODY, true);
            }
        }

        if ($req->headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter($req->headers, function ($v, $k) {
                return $k . ": " . $v;
            }, ARRAY_FILTER_USE_BOTH));
        }

        curl_setopt($ch, CURLOPT_USERAGENT, $req->userAgent ?? $this->defaultUserAgent);
        if ($req->timeOut > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $req->timeOut);
        }

        if ($req->connectTimeout > 0) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $req->connectTimeout);
        }

        return new RelayCurlResponse($ch);
    }

    /**
     * @return never
     */
    final public function listen(): never
    {
        if ($this->ipWhitelist && !in_array($_SERVER["REMOTE_ADDR"], $this->ipWhitelist)) {
            http_response_code(403);
            exit();
        }

        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            http_response_code(204);
            exit();
        }

        try {
            $request = $this->decrypt(base64_decode(file_get_contents("php://input")));
        } catch (CipherException $e) {
            http_response_code(451);
            exit($e->getMessage());
        }

        if (strtolower($request->method) === "handshake") {
            http_response_code(202);
            exit();
        }

        try {
            $response = $this->relayCurlRequest($request);
        } catch (RelayCurlException $e) {
            if ($e->curlHandle) {
                http_response_code(453);
                exit(curl_errno($e->curlHandle) . "\t" . curl_error($e->curlHandle));
            }

            http_response_code(452);
            exit($e->getCode() . "\t" . $e->getMessage());
        }

        try {
            $encrypted = $this->encrypt($response);
        } catch (CipherException $e) {
            http_response_code(454);
            exit($e->getMessage());
        }

        http_response_code(250);
        exit(base64_encode($encrypted));
    }
}
