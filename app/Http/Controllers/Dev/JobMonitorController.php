<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class JobMonitorController extends Controller
{
    public function index()
    {
        // Paginate jobs (20 per page)
        $jobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->orderBy('id')
            ->paginate(20)
            ->through(function ($job) {
                $payload = json_decode($job->payload, true);

                $displayName = $payload['displayName'] ?? 'N/A';
                $data = $payload['data'] ?? [];

                return (object) [
                'id'           => $job->id,
                'queue'        => $job->queue,
                'attempts'     => $job->attempts,
                'reserved_at'  => $job->reserved_at,
                'available_at' => $job->available_at,
                'created_at'   => $job->created_at,
                'displayName'  => $displayName,
                'dynamicData'  => $data,
                'isRunning'    => $job->reserved_at !== null,   // flag running jobs
            ];
            });

        return view('pages.dev.devtools.jobs.index', compact('jobs'));
    }

    public function failed()
    {
        $failedJobs = DB::table('failed_jobs')
            ->select('id', 'connection', 'queue', 'payload', 'exception', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->paginate(20)
            ->through(function ($job) {
                $payload = json_decode($job->payload, true);
                $displayName = $payload['displayName'] ?? 'N/A';
                $data = $payload['data'] ?? [];

                return (object) [
                    'id'          => $job->id,
                    'connection'  => $job->connection,
                    'queue'       => $job->queue,
                    'displayName' => $displayName,
                    'exception'   => $job->exception,
                    'failed_at'   => $job->failed_at,
                    'dynamicData' => $data,
                ];
            });

        return view('pages.dev.devtools.jobs.failed', compact('failedJobs'));
    }
}
