<?php

namespace App\Http\Controllers\Dev;

use App\Enum\Permissions\DeveloperEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseBackUpController extends Controller
{
    protected string $disk = 'mybackup';
    protected string $backupDir = 'database-backup';

    public function index()
    {
        $this->authorize(DeveloperEnum::DatabaseBackup);
        $disk = Storage::disk($this->disk);

        $files = collect($disk->files($this->backupDir))
            ->filter(fn($path) => Str::endsWith($path, '.zip')) // only zip backups
            ->map(function ($path) use ($disk) {
                return [
                    'path' => $path,
                    'file' => basename($path),
                    'size' => $disk->size($path),
                    'date' => Carbon::createFromTimestamp($disk->lastModified($path)),
                ];
            })
            ->sortByDesc('date')
            ->values();

        return view('pages.dev.devtools.databseBackup.index', compact('files'));
    }

    public function download(string $file)
    {
        $this->authorize(DeveloperEnum::DatabaseBackup);
        $disk = Storage::disk($this->disk);

        // Protect from path traversal
        $file = basename($file);

        $path = "{$this->backupDir}/{$file}";

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->download($path);
    }
}
