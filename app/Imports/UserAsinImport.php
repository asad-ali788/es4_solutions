<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\UserAssignedAsin;

class UserAsinImport implements ToCollection
{
    public function __construct(private int $userId, private int $assignedById) {}

    public function collection(Collection $rows)
    {
        // Remove header row (assuming first row is a header)
        $asins = $rows->skip(1)->pluck(0)->filter()->toArray();

        // Prepare data for upsert
        $data = collect($asins)->map(function ($asin) {
            return [
                'user_id'        => $this->userId,
                'asin'           => trim($asin),
                'assigned_by_id' => $this->assignedById,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        })->toArray();

        // Insert/update
        UserAssignedAsin::upsert(
            $data,
            ['user_id', 'asin'],
            ['assigned_by_id', 'updated_at']
        );

        // Delete ASINs that were removed from file
        UserAssignedAsin::where('user_id', $this->userId)
            ->whereNotIn('asin', $asins)
            ->delete();
    }
}
