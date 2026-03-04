<?php

namespace App\Exports;

use App\Models\ProductForecast;
use App\Models\ProductForecastAsins;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\{
    WithMultipleSheets,
    FromArray,
    WithHeadings,
    WithTitle,
    WithColumnWidths,
    WithColumnFormatting
};

class OrderForecastFinaliseExport implements WithMultipleSheets
{
    protected array $months;

    public function __construct()
    {
        $start = now()->startOfYear();

        $this->months = collect(range(0, 11))
            ->map(fn($i) => $start->copy()->addMonths($i)->format('Y-m'))
            ->toArray();
    }

    public function sheets(): array
    {
        return [
            $this->asinSheet(),
            $this->skuSheet(),
        ];
    }

    /* ================= ASIN SHEET ================= */

    protected function asinSheet(): object
    {
        return new class($this->months)
        implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithColumnFormatting
        {
            public function __construct(private array $months) {}

            public function title(): string
            {
                return 'ASIN Forecast';
            }

            public function headings(): array
            {
                return array_merge(
                    ['ASIN'],
                    array_map(
                        fn($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'),
                        $this->months
                    )
                );
            }

            public function array(): array
            {
                return ProductForecastAsins::select(
                    'product_asin',
                    'forecast_month',
                    'forecast_units'
                )
                    ->get()
                    ->groupBy('product_asin')
                    ->map(function ($records, $asin) {
                        $monthMap = $records->mapWithKeys(fn($r) => [
                            Carbon::parse($r->forecast_month)->format('Y-m') => (int) $r->forecast_units
                        ]);

                        return array_merge(
                            [$asin],
                            array_map(fn($m) => isset($monthMap[$m]) && $monthMap[$m] !== null ? (int) $monthMap[$m] : 0, $this->months)
                        );
                    })
                    ->values()
                    ->toArray();
            }

            public function columnWidths(): array
            {
                return $this->buildWidths(18, 14);
            }

            public function columnFormats(): array
            {
                return $this->buildFormats();
            }

            /* ---------- Helpers ---------- */

            private function buildWidths(int $firstCol, int $monthCol): array
            {
                $widths = ['A' => $firstCol];

                foreach (range(1, count($this->months)) as $i) {
                    $widths[$this->col($i)] = $monthCol;
                }

                return $widths;
            }

            private function buildFormats(): array
            {
                $formats = [];

                foreach (range(1, count($this->months)) as $i) {
                    $formats[$this->col($i)] = NumberFormat::FORMAT_NUMBER;
                }

                return $formats;
            }

            private function col(int $index): string
            {
                return Coordinate::stringFromColumnIndex($index + 1);
            }
        };
    }

    /* ================= SKU SHEET ================= */

    protected function skuSheet(): object
    {
        return new class($this->months)
        implements FromArray, WithHeadings, WithTitle, WithColumnWidths, WithColumnFormatting
        {
            public function __construct(private array $months) {}

            public function title(): string
            {
                return 'SKU Forecast';
            }

            public function headings(): array
            {
                return array_merge(
                    ['Product ID'],
                    array_map(
                        fn($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'),
                        $this->months
                    )
                );
            }

            public function array(): array
            {
                // Eager load the product to get SKU
                $forecasts = ProductForecast::with('product:id,sku')
                    ->select('product_id', 'forecast_month', 'forecast_units')
                    ->get()
                    ->groupBy('product_id');

                return $forecasts->map(function ($records, $productId) {
                    $product = $records->first()->product; // fetch the related product
                    $sku = $product?->sku ?? $productId; // fallback to ID if SKU missing

                    $monthMap = $records->mapWithKeys(fn($r) => [
                        Carbon::parse($r->forecast_month)->format('Y-m') => (int) $r->forecast_units
                    ]);

                    return array_merge(
                        [$sku],
                        array_map(fn($m) => $monthMap[$m] ?? 0, $this->months)
                    );
                })
                    ->values()
                    ->toArray();
            }


            public function columnWidths(): array
            {
                return $this->buildWidths(25, 14);
            }

            public function columnFormats(): array
            {
                return $this->buildFormats();
            }

            /* ---------- Helpers ---------- */

            private function buildWidths(int $firstCol, int $monthCol): array
            {
                $widths = ['A' => $firstCol];

                foreach (range(1, count($this->months)) as $i) {
                    $widths[$this->col($i)] = $monthCol;
                }

                return $widths;
            }

            private function buildFormats(): array
            {
                $formats = [];

                foreach (range(1, count($this->months)) as $i) {
                    $formats[$this->col($i)] = NumberFormat::FORMAT_NUMBER;
                }

                return $formats;
            }

            private function col(int $index): string
            {
                return Coordinate::stringFromColumnIndex($index + 1);
            }
        };
    }
}
