<?php

namespace App\Http\Controllers\Dev;

use App\Enum\Permissions\DeveloperEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(DeveloperEnum::SchedulesList);
        // Allowed timezones for the dropdown
        $timezones = [
            'Asia/Kolkata'       => 'IST (Asia/Kolkata)',
            'America/Los_Angeles' => 'PST (US - Los Angeles)',
            'America/Vancouver'  => 'PST (Canada - Vancouver)',
            'UTC'                => 'UTC',
        ];

        // Get selected timezone from query, default to app timezone (probably Asia/Kolkata)
        $timezone = $request->input('timezone', config('app.timezone'));

        // Ensure timezone is one of our allowed values
        if (! array_key_exists($timezone, $timezones)) {
            $timezone = config('app.timezone');
        }

        // Run the schedule:list command as JSON with selected timezone
        Artisan::call('schedule:list', [
            '--json'     => true,
            '--timezone' => $timezone,
        ]);

        $output = Artisan::output();

        // Decode JSON into collection
        $tasks = collect(json_decode($output, true) ?: []);

        return view('pages.dev.devtools.schedule.index', compact('tasks', 'timezone', 'timezones'));
    }
}
