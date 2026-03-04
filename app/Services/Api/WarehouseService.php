<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WarehouseService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.warehouse.endpoint'), '/');
        $this->token   = config('services.warehouse.token');
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->retry(3, 200)
            ->timeout(60)
            ->throw(function ($response, $e) {
                // Log the error details
                Log::error('API Request Failed', [
                    'url'        => $response->effectiveUri(),
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                    'exception'  => $e?->getMessage(),
                ]);
            });
    }

    public function queryList(int $pageSize = 500): array
    {
        $all       = [];
        $curPageNo = 1;

        do {
            $response = $this->client()->get('/open-api/oms/product/queryList', [
                'pageSize'   => $pageSize,
                'curPageNo'  => $curPageNo,
            ]);

            if (!$response->successful()) break;

            $data    = $response->json('data');
            $records = $data['records'] ?? [];

            $all = array_merge($all, $records);
            $curPageNo++;
        } while ($curPageNo <= ($data['pages'] ?? 1));

        return $all;
    }

    public function stockList(int $pageSize = 100): array
    {
        $all       = [];
        $curPageNo = 1;
        do {
            $response = $this->client()->get('/open-api/oms/stock/list', [
                'pageSize'  => $pageSize,
                'curPageNo' => $curPageNo,
            ]);
            if (!$response->successful()) {
                break;
            }
            $records = $response->json('data.records') ?? [];
            $got     = count($records);

            if ($got === 0) {
                break;
            }
            $all = array_merge($all, $records);
            $curPageNo++;
        } while ($got === $pageSize);
        return $all;
    }
}
