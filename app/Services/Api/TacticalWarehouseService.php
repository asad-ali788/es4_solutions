<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TacticalWarehouseService
{
    protected string $baseUrl;
    protected string $email;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.tactical.endpoint'), '/');
        $this->email    = config('services.tactical.email');
        $this->password = config('services.tactical.password');
    }

    public function getToken(): ?string
    {
        $response = Http::post("{$this->baseUrl}/AccountUser/Login", [
            'Email'    => $this->email,
            'Password' => $this->password,
        ]);

        if ($response->successful() && isset($response['Token'])) {
            return $response['Token'];
        }

        Log::error('Tactical Warehouse: Failed to retrieve token', [
            'status'   => $response->status(),
            'response' => $response->json(),
        ]);
        return null;
    }

    public function getInventory(): ?array
    {
        $token = $this->getToken();
        if (!$token) {
            Log::warning('Tactical Warehouse: Token retrieval failed, skipping inventory sync');
            return null;
        }
        $today    = now()->format('Y/m/d');
        $response = Http::withToken($token)
            ->timeout(60)
            ->acceptJson()
            ->post("{$this->baseUrl}/API/InventoryCount", [
                'from' => $today,
                'to'   => $today,
            ]);

        if ($response->successful()) {
            return $response->json('response.Body') ?? [];
        }
        Log::error('Tactical Warehouse: Inventory fetch failed', [
            'status'   => $response->status(),
            'response' => $response->json(),
        ]);
        return null;
    }
}
