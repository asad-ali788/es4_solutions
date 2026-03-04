<?php

namespace App\Services\Ads;

use AmazonAdvertisingApi\Client;
use AmazonAdvertisingApi\CurlRequest;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AmazonAdsJsonClient extends Client
{
    protected $config;
    protected $userAgent;
    protected $headerAccept;
    protected $endpoint;
    public $headers;
    private $tokenUrl;

    public function __construct(array $config)
    {
        // Merge defaults
        $defaults = [
            'isUseProxy' => false,
            'curlProxy' => '',
            'curlProxyType' => 'http',
            'appUserAgent' => 'MyLaravelAmazonAdsApp',
            'headerAccept' => 'application/json',
            'saveFile' => true,
            'sandbox' => false,
            'apiVersion' => 'v1',
            'sbVersion' => 'v4',
            'sdVersion' => 'v3',
            'spVersion' => 'v3',
            'portfoliosVersion' => 'v1',
            'reportsVersion' => 'v3',
        ];

        // Merge user config with defaults
        $this->config = array_merge($defaults, $config);

        // Map region to endpoint AND token URL **before parent constructor**
        $region = strtolower($this->config['region'] ?? 'na');

        $this->endpoint = match ($region) {
            'na' => 'https://advertising-api.amazon.com',
            'eu' => 'https://advertising-api-eu.amazon.com',
            'fe' => 'https://advertising-api-fe.amazon.com',
            default => throw new \Exception("Unknown Amazon Ads API region: {$region}"),
        };

        $this->tokenUrl = match ($region) {
            'na' => 'https://api.amazon.com/auth/o2/token',
            'eu' => 'https://api.amazon.co.uk/auth/o2/token',
            'fe' => 'https://api.amazon.co.jp/auth/o2/token',
            default => throw new \Exception("Unknown Amazon Ads API token URL for region: {$region}"),
        };

        // Now call parent constructor
        parent::__construct($this->config);

        $this->userAgent = $this->config['userAgent'] ?? $this->config['appUserAgent'];
        $this->headerAccept = $this->config['headerAccept'] ?? 'application/json';
        $this->headers = [];
    }


    /**
     * Refresh Amazon Ads access token using the refresh token
     *
     * @return array
     * @throws Exception
     */
    public function doRefreshToken(): array
    {
        if (empty($this->tokenUrl)) {
            $this->logAndThrow("Token URL is not set. Check your region configuration.");
        }

        $headers = [
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
            "User-Agent: $this->userAgent"
        ];

        $refresh_token = rawurldecode($this->config['refreshToken']);

        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->config['clientId'],
            'client_secret' => $this->config['clientSecret']
        ];

        // Convert params to URL-encoded string
        $data = http_build_query($params);

        $url = $this->tokenUrl; // Use the full URL from endpoints

        $request = new CurlRequest($this->config);
        $request->setOption(CURLOPT_URL, $url);
        $request->setOption(CURLOPT_HTTPHEADER, $headers);
        $request->setOption(CURLOPT_USERAGENT, $this->userAgent);
        $request->setOption(CURLOPT_POST, true);
        $request->setOption(CURLOPT_POSTFIELDS, $data);
        $request->setOption(CURLOPT_RETURNTRANSFER, true);
        $request->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $request->setOption(CURLOPT_SSL_VERIFYHOST, 2);

        // Execute request
        $response = $request->execute();
        $responseInfo = $request->getInfo();
        $request->close();

        // Log raw response for debugging
        // Log::info('Amazon Token Refresh Response:', [
        //     'http_code' => $responseInfo['http_code'],
        //     'response' => $response
        // ]);

        // Check HTTP status code
        if ($responseInfo['http_code'] < 200 || $responseInfo['http_code'] >= 300) {
            throw new \Exception("Failed to refresh Amazon Ads token: HTTP {$responseInfo['http_code']} - {$response}");
        }

        $responseArray = json_decode($response, true);

        if (!is_array($responseArray) || empty($responseArray['access_token'])) {
            throw new \Exception("Failed to refresh Amazon Ads token: 'access_token' missing in response. Raw response: {$response}");
        }

        // Save new access token
        $this->config['accessToken'] = $responseArray['access_token'];

        return $responseArray;
    }


    /**
     * Override customOperation to automatically refresh token if missing or expired.
     */
    public function customOperation(string $interface, ?array $params = [], string $method = "GET", bool $needAccept = true): array
    {
        if (empty($this->config['accessToken'])) {
            $this->doRefreshToken();
        }

        $headers = [
            'Authorization: bearer ' . $this->config['accessToken'],
            'User-Agent: ' . $this->userAgent,
            'Amazon-Advertising-API-ClientId: ' . $this->config['clientId'],
            'Content-Type: application/json',
        ];


        if (!empty($this->profileId)) {
            $headers[] = 'Amazon-Advertising-API-Scope: ' . $this->profileId;
        }

        if ($needAccept && $this->headerAccept) {
            $headers[] = 'Accept: ' . $this->headerAccept;
        }

        $this->headers = $headers;

        // Ensure CurlRequest config always has required keys
        $curlConfig = array_merge([
            'isUseProxy' => false,
            'curlProxy' => '',
            'curlProxyType' => 'http',
        ], $this->config);

        $request = new CurlRequest($curlConfig);
        $url = rtrim($this->endpoint, '/') . '/' . $interface;

        if (strtolower($method) === 'get' && !empty($params)) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        if (in_array(strtolower($method), ['post', 'put', 'delete']) && !empty($params)) {
            $request->setOption(CURLOPT_POSTFIELDS, json_encode($params));
        }

        $request->setOption(CURLOPT_URL, $url);
        $request->setOption(CURLOPT_HTTPHEADER, $headers);
        $request->setOption(CURLOPT_USERAGENT, $this->userAgent);
        $request->setOption(CURLOPT_CUSTOMREQUEST, strtoupper($method));

        $response = $this->executeRequest($request);

        // If unauthorized, refresh token once and retry
        if ($response['code'] === 401) {
            $this->doRefreshToken();
            $headers[0] = 'Authorization: bearer ' . $this->config['accessToken'];
            $request->setOption(CURLOPT_HTTPHEADER, $headers);
            $response = $this->executeRequest($request);
        }

        return $response;
    }

    private function logAndThrow($message)
    {
        throw new Exception($message);
    }
}
