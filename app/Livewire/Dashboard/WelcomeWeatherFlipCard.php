<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\OpenWeatherService;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Component;

class WelcomeWeatherFlipCard extends Component
{
    public string $locationKey = 'us_ca';
    public string $region = '';
    public string $country = '';

    /**
     * @var array<string, array{label:string, region:string, country:string, tz:string}>
     */
    public array $locations = [
        'us_ca' => [
            'label'   => 'US — California',
            'region'  => 'California',
            'country' => 'US',
            'tz'      => 'America/Los_Angeles',
        ],
        'us_tx' => [
            'label'   => 'US — Texas',
            'region'  => 'Texas',
            'country' => 'US',
            'tz'      => 'America/Chicago',
        ],
        'us_fl' => [
            'label'   => 'US — Florida',
            'region'  => 'Florida',
            'country' => 'US',
            'tz'      => 'America/New_York',
        ],
        'us_ny' => [
            'label'   => 'US — New York',
            'region'  => 'New York',
            'country' => 'US',
            'tz'      => 'America/New_York',
        ],
        'ca_on' => [
            'label'   => 'Canada — Ontario',
            'region'  => 'Ontario',
            'country' => 'CA',
            'tz'      => 'America/Toronto',
        ],
        'ca_qc' => [
            'label'   => 'Canada — Quebec',
            'region'  => 'Quebec',
            'country' => 'CA',
            'tz'      => 'America/Toronto',
        ],
        'ca_bc' => [
            'label'   => 'Canada — British Columbia',
            'region'  => 'British Columbia',
            'country' => 'CA',
            'tz'      => 'America/Vancouver',
        ],
        'ca_ab' => [
            'label'   => 'Canada — Alberta',
            'region'  => 'Alberta',
            'country' => 'CA',
            'tz'      => 'America/Edmonton',
        ],
        'in_chennai' => [
            'label'   => 'India — Chennai',
            'region'  => 'Chennai',
            'country' => 'IN',
            'tz'      => 'Asia/Kolkata',
        ],
    ];

    /** @var array{ok:bool,error:?string,days:array,meta:array} */
    public array $forecast = [
        'ok'    => false,
        'error' => null,
        'days'  => [],
        'meta'  => [],
    ];

    public function mount(): void
    {
        $this->applyLocation($this->locationKey);
        $this->refreshForecast();
    }

    public function setLocation(string $key): void
    {
        if (!array_key_exists($key, $this->locations)) {
            $key = 'us_ca';
        }

        // Avoid extra requests if same value
        if ($this->locationKey === $key) {
            return;
        }

        $this->locationKey = $key;
        $this->applyLocation($key);

        $this->refreshForecast();
    }

    public function refreshForecast(): void
    {
        /** @var OpenWeatherService $weather */
        $weather = app(OpenWeatherService::class);

        $raw = $weather->getDailyForecastForNext5Days($this->region, $this->country);

        // ✅ Critical: normalize to JSON-safe scalars
        $this->forecast = $this->normalizeForLivewire($raw);
    }

    private function applyLocation(string $key): void
    {
        $loc = Arr::get($this->locations, $key, $this->locations['us_ca']);

        $this->region  = (string) ($loc['region'] ?? '');
        $this->country = (string) ($loc['country'] ?? '');
    }

    /**
     * Convert Collections/Carbon/objects into plain arrays of scalars.
     * This prevents Livewire hydration corruption.
     *
     * @param mixed $value
     * @return array
     */
    private function normalizeForLivewire(mixed $value): array
    {
        // Convert collections to array first
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        // If service returned object, try to cast safely
        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } else {
                // Last resort: encode/decode (drops non-serializable bits)
                $value = json_decode(json_encode($value), true) ?: [];
            }
        }

        // If it’s not an array by now, force a safe structure
        if (!is_array($value)) {
            return [
                'ok'    => false,
                'error' => 'Weather service returned invalid payload.',
                'days'  => [],
                'meta'  => [],
            ];
        }

        // Deep-normalize any nested objects (Carbon/DateTime/etc.)
        $normalized = json_decode(json_encode($value), true);

        if (!is_array($normalized)) {
            return [
                'ok'    => false,
                'error' => 'Weather payload could not be normalized.',
                'days'  => [],
                'meta'  => [],
            ];
        }

        // Ensure expected keys exist to avoid undefined indexes in blade
        return [
            'ok'    => (bool) ($normalized['ok'] ?? false),
            'error' => $normalized['error'] ?? null,
            'days'  => is_array($normalized['days'] ?? null) ? $normalized['days'] : [],
            'meta'  => is_array($normalized['meta'] ?? null) ? $normalized['meta'] : [],
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.welcome-weather-flip-card');
    }
}
