<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SplFileObject;
use Generator;

class AmazonReportParser
{
    /**
     * Stream-parse a tab-separated Amazon SP-API report into array data
     */
    public function parse(string $reportId, string $disk = 'public'): Generator
    {
        $directory = Storage::disk($disk)->path('api/reports');
        $matches   = glob($directory . "/*_report_{$reportId}.txt");

        if (empty($matches)) {
            Log::warning("Report file not found for report ID {$reportId}");
            throw new \RuntimeException("Report file not found for ID: {$reportId}");
        }

        $file = new SplFileObject($matches[0]);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl("\t");

        $header = null;

        foreach ($file as $row) {
            if ($row === false || empty($row) || (count($row) === 1 && trim($row[0]) === '')) {
                continue;
            }

            if ($file->key() === 0) {
                $header = $row;
                continue;
            }

            if ($header) {
                $row = array_map(fn($v) => iconv('Windows-1252', 'UTF-8//IGNORE', $v), $row);

                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), null);
                } elseif (count($row) > count($header)) {
                    $row = array_slice($row, 0, count($header));
                }

                yield array_combine($header, $row);
            }
        }
    }
}
