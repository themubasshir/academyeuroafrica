<?php

namespace App\Services\Joomla;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Server-to-server client for the EAMN Academy Bridge Joomla plugin.
 *
 * All calls are authenticated with a shared HMAC secret sent in a header —
 * never in the URL. The plugin endpoint lives outside the public Joomla
 * surface where possible and always verifies the signature itself.
 */
class BridgeClient
{
    private string $baseUrl;

    private string $secret;

    private string $clientId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.joomla.base_url'), '/');
        $this->secret = (string) config('services.joomla.bridge_secret');
        $this->clientId = (string) config('services.joomla.client_id', 'eamn-academy');
    }

    /**
     * Exchange a single-use authorisation code for identity claims.
     *
     * @return array{sub:string,email:string,name:string,groups:array<int,string>}
     */
    public function exchangeCode(string $code, string $state): array
    {
        $response = $this->signedPost('/index.php?option=com_eamnbridge&task=exchange', [
            'code' => $code,
            'state' => $state,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Bridge code exchange failed: '.$response->status());
        }

        $claims = $response->json();

        if (! is_array($claims)) {
            throw new RuntimeException('Bridge response is not valid JSON.');
        }

        foreach (['sub', 'email', 'name'] as $key) {
            if (! isset($claims[$key]) || ! is_string($claims[$key]) || trim($claims[$key]) === '') {
                throw new RuntimeException("Bridge response missing claim: {$key}");
            }
        }

        if (! filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Bridge response contains invalid email.');
        }

        if (! preg_match('/^[1-9][0-9]*$/', $claims['sub'])) {
            throw new RuntimeException('Bridge response contains invalid subject.');
        }

        if (isset($claims['groups']) && ! is_array($claims['groups'])) {
            throw new RuntimeException('Bridge response contains invalid groups.');
        }

        return [
            'sub' => $claims['sub'],
            'email' => $claims['email'],
            'name' => $claims['name'],
            'groups' => array_values(array_filter($claims['groups'] ?? [], 'is_string')),
        ];
    }

    public function signedPost(string $path, array $payload): Response
    {
        if ($this->baseUrl === '' || $this->secret === '') {
            throw new RuntimeException('Joomla bridge is not configured.');
        }

        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp."\n".$body, $this->secret);

        try {
            return Http::withHeaders([
                'X-EAMN-Client' => $this->clientId,
                'X-EAMN-Timestamp' => $timestamp,
                'X-EAMN-Signature' => $signature,
            ])
                ->withBody($body, 'application/json')
                ->timeout(10)
                ->post($this->baseUrl.$path);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Joomla bridge unreachable.', previous: $e);
        }
    }
}
