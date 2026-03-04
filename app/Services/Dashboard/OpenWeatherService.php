<?php

namespace App\Services\Dashboard;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWeatherService
{
    public function getDailyForecastForNext5Days(?string $city = null, ?string $country = null): array
    {
        $apiKey = (string) config('services.openweather.key');
        $base   = rtrim((string) config('services.openweather.base'), '/');
        $units  = (string) config('services.openweather.units', 'metric');

        $city    = $city ?: (string) config('services.openweather.city');
        $country = $country ?: (string) config('services.openweather.country');

        if (blank($apiKey)) {
            return $this->fail('Missing OPENWEATHER_API_KEY in .env');
        }

        if (blank($base) || blank($city) || blank($country)) {
            return $this->fail('OpenWeather configuration is incomplete.');
        }

        $cacheKey = 'openweather:forecast5:' . md5($city . '|' . $country . '|' . $units);

        // Use tags if supported
        $cache = Cache::supportsTags()
            ? Cache::tags(['dashboard', 'weather'])
            : Cache::store();

        // Return cached success if present
        $cached = $cache->get($cacheKey);

        if (is_array($cached) && ($cached['ok'] ?? false) === true) {
            return $cached;
        }

        // Fetch fresh
        $result = $this->fetchAndAggregate($base, $apiKey, $city, $country, $units);

        if (($result['ok'] ?? false) === true) {
            $cache->put($cacheKey, $result, now()->addMinutes(30));
            Log::info('Cached weather data (30 min)', ['key' => $cacheKey]);
        } else {
            $cache->put($cacheKey . ':fail', $result, now()->addMinutes(2));
            Log::warning('Short cached weather failure (2 min)', ['key' => $cacheKey]);
        }

        return $result;
    }

    private function fetchAndAggregate(string $base, string $apiKey, string $city, string $country, string $units): array
    {
        try {
            $response = Http::timeout(8)
                ->retry(2, 200)
                ->get($base . '/forecast', [
                    'q'     => $city . ',' . $country,
                    'appid' => $apiKey,
                    'units' => $units,
                ])
                ->throw();

            $data = $response->json();
            if (!is_array($data)) {
                return $this->fail('OpenWeather returned invalid JSON.');
            }

            // OpenWeather error payload often has cod != 200
            $cod = $data['cod'] ?? null;
            if ($cod !== null && (string) $cod !== '200') {
                $msg = (string) ($data['message'] ?? 'Unknown error');
                return $this->fail('OpenWeather error: ' . $msg);
            }

            $list = $data['list'] ?? null;
            if (!is_array($list) || empty($list)) {
                return $this->fail('OpenWeather returned no forecast data.');
            }

            $cityMeta = is_array($data['city'] ?? null) ? $data['city'] : [];
            $tzOffset = (int) ($cityMeta['timezone'] ?? 0);

            $daily = [];

            foreach ($list as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $dtUnix = $item['dt'] ?? null;
                if (!is_numeric($dtUnix)) {
                    continue;
                }

                $dt = CarbonImmutable::createFromTimestampUTC((int) $dtUnix);
                $local = $dt->addSeconds($tzOffset);

                $dayKey = $local->format('Y-m-d');

                $main = is_array($item['main'] ?? null) ? $item['main'] : [];
                $tempMin = (float) ($main['temp_min'] ?? $main['temp'] ?? 0);
                $tempMax = (float) ($main['temp_max'] ?? $main['temp'] ?? 0);

                $weather0 = (is_array($item['weather'] ?? null) && isset($item['weather'][0]) && is_array($item['weather'][0]))
                    ? $item['weather'][0]
                    : [];

                $icon = (string) ($weather0['icon'] ?? '');
                $desc = (string) ($weather0['description'] ?? '');

                $windArr = is_array($item['wind'] ?? null) ? $item['wind'] : [];
                $wind = (float) ($windArr['speed'] ?? 0);

                if (!isset($daily[$dayKey])) {
                    $daily[$dayKey] = [
                        'date'      => $dayKey,
                        'label'     => $local->format('D, M j'),
                        'min'       => $tempMin,
                        'max'       => $tempMax,
                        'wind'      => $wind,
                        'icon'      => $icon,
                        'desc'      => $desc,
                        'pickScore' => $this->scoreSlotForIconPick($local),
                    ];
                    continue;
                }

                $daily[$dayKey]['min']  = min($daily[$dayKey]['min'], $tempMin);
                $daily[$dayKey]['max']  = max($daily[$dayKey]['max'], $tempMax);
                $daily[$dayKey]['wind'] = max($daily[$dayKey]['wind'], $wind);

                $score = $this->scoreSlotForIconPick($local);
                if ($score > $daily[$dayKey]['pickScore'] && filled($icon)) {
                    $daily[$dayKey]['icon'] = $icon;
                    $daily[$dayKey]['desc'] = $desc;
                    $daily[$dayKey]['pickScore'] = $score;
                }
            }

            if (empty($daily)) {
                return $this->fail('OpenWeather returned no usable daily data.');
            }

            ksort($daily);

            $days = array_values($daily);
            $days = array_slice($days, 0, 5);

            $days = array_map(function (array $d) {
                unset($d['pickScore']);
                $d['min'] = round((float) ($d['min'] ?? 0));
                $d['max'] = round((float) ($d['max'] ?? 0));
                $d['wind'] = round((float) ($d['wind'] ?? 0), 1);
                $d['icon'] = (string) ($d['icon'] ?? '');
                $d['desc'] = (string) ($d['desc'] ?? '');
                $d['date'] = (string) ($d['date'] ?? '');
                $d['label'] = (string) ($d['label'] ?? '');
                return $d;
            }, $days);

            return [
                'ok'    => true,
                'error' => null,
                'days'  => $days,
                'meta'  => [
                    'city'            => (string) ($cityMeta['name'] ?? $city),
                    'country'         => (string) ($cityMeta['country'] ?? $country),
                    'units'           => $units,
                    'timezone_offset' => $tzOffset,
                ],
            ];
        } catch (RequestException $e) {
            return $this->fail('OpenWeather request failed: ' . $e->getMessage());
        }
    }

    private function fail(string $message): array
    {
        return [
            'ok'    => false,
            'error' => $message,
            'days'  => [],
            'meta'  => [],
        ];
    }

    private function scoreSlotForIconPick(CarbonImmutable $local): int
    {
        $hour = (int) $local->format('G');
        return 24 - abs(12 - $hour);
    }
}
