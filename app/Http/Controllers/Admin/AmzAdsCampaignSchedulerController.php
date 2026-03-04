<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Http\Controllers\Controller;
use App\Models\AmzAdsCampaignSchedule;
use App\Models\AmzAdsCampaignsUnderSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AmzCampaigns;
use App\Models\AmzCampaignsSb;
use App\Models\AmzCampaignsSd;
use Illuminate\Support\Facades\Log;

class AmzAdsCampaignSchedulerController extends Controller
{
    // Campaign Under Schedules
    public function activeCampaigns(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignSchedule);
        try {
            $query = AmzAdsCampaignsUnderSchedule::query();
            // Apply search filter if provided
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where('campaign_id', 'like', "%{$search}%");
            }
            if ($request->filled('country') && $request->country !== 'all') {
                $query->where('country', $request->country);
            }
            if ($request->filled('campaign') && $request->campaign !== 'all') {
                $query->where('campaign_type', $request->campaign);
            }
            // Filter only Enabled campaigns
            $query->where('campaign_status', 'Enabled');
            $campaigns = $query->paginate($request->get('per_page', 50));
            return view('pages.admin.amzAds.campaignSchedule.activeCampaigns', compact('campaigns'));
        } catch (\Throwable $e) {
            Log::error('Error in underSchedule(): ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Something went wrong while fetching campaigns.');
        }
    }

    public function runStatus(Request $request)
    {
        try {
            $schedule = AmzAdsCampaignsUnderSchedule::where('campaign_id', $request->campaign_id)->firstOrFail();

            $schedule->update([
                'run_status'      => $request->status,
                'last_updated'    => now(),
            ]);
            return back()->with(
                'success',
                'Campaign status updated to ' . ($request->status ? 'Enabled' : 'Disabled')
            );
        } catch (\Throwable $e) {
            Log::error("Error updating run_status", [
                'error' => $e->getMessage(),
            ]);
            return back()->with(
                'error',
                'Something went wrong while updating run status'
            );
        }
    }

    public function enable($campaign_id)
    {
        try {
            // Try campaign lookup in any of the 3 tables
            $campaign = collect([
                AmzCampaigns::select('campaign_id', 'campaign_type', 'country')->where('campaign_id', $campaign_id)->first(),
                AmzCampaignsSb::select('campaign_id', 'campaign_type', 'country')->where('campaign_id', $campaign_id)->first(),
                AmzCampaignsSd::select('campaign_id', 'campaign_type', 'country')->where('campaign_id', $campaign_id)->first(),
            ])->filter()->first();
            if (!$campaign) {
                return back()->with('error', "Campaign not found in any campaign tables.");
            }
            $timestamp = now();

            $schedule = AmzAdsCampaignsUnderSchedule::firstOrNew([
                'campaign_id'   => $campaign->campaign_id,
                'campaign_type' => $campaign->campaign_type,
            ]);

            // Toggle if exists, otherwise set as enabled
            $newStatus = $schedule->exists ? !$schedule->run_status : true;

            $schedule->fill([
                'user_id'         => auth()->id(),
                'added'           => $schedule->exists ? $schedule->added : $timestamp,
                'last_updated'    => $timestamp,
                'country'         => $campaign->country,
                'campaign_status' => $newStatus ? 'ENABLED' : 'PAUSED',
                'run_status'      => $newStatus,
            ])->save();

            return back()->with('success', "Campaign Schedule status set to " . ($newStatus ? 'Enabled' : 'PAUSED'));
        } catch (\Throwable $e) {
            Log::error("Error in Campaign Schedule", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', "Something went wrong while updating the Campaign Schedule.");
        }
    }

    // Schedule by Week page
    public function index()
    {
        $campaigns = AmzAdsCampaignSchedule::paginate(50);
        return view('pages.admin.amzAds.campaignSchedule.timelines.index', compact('campaigns'));
    }

    // Show create form
    public function create()
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignAddSchedule);
        return view('pages.admin.amzAds.campaignSchedule.timelines.form')
            ->with('schedule', null);
    }

    // Store new schedule
    public function store(Request $request)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignAddSchedule);
        $validated = $this->validateSchedule($request);
        // Ensure unique for country + day_of_week
        if ($this->scheduleExists($validated['day_of_week'], $validated['country'])) {
            return back()->withErrors([
                'day_of_week' => 'Schedule already exists for this country and day of week.',
            ])->withInput();
        }
        // Normalize fields
        $data = $this->prepareScheduleData($validated);
        AmzAdsCampaignSchedule::create($data);

        return to_route('admin.ads.schedule.index')
            ->with('success', 'Campaign Schedule created successfully.');
    }

    // Show edit form
    public function edit($id)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignUpdateSchedule);
        $schedule = AmzAdsCampaignSchedule::findOrFail($id);
        return view('pages.admin.amzAds.campaignSchedule.timelines.form')
            ->with('schedule', $schedule);
    }

    public function update(Request $request, $id)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsCampaignUpdateSchedule);
        $validated = $this->validateSchedule($request);
        $schedule  = AmzAdsCampaignSchedule::findOrFail($id);

        // Uniqueness check except current ID
        if ($this->scheduleExists($validated['day_of_week'], $validated['country'], $id)) {
            return back()->withErrors([
                'day_of_week' => 'Schedule already exists for this country and day of week.',
            ])->withInput();
        }
        // Normalize fields
        $data = $this->prepareScheduleData($validated);
        $schedule->update($data);

        return to_route('admin.ads.schedule.index')
            ->with('success', 'Campaign Schedule updated successfully.');
    }

    private function validateSchedule(Request $request): array
    {
        return $request->validate([
            'day_of_week' => 'required|string',
            'country'     => 'required|string|max:3',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
        ]);
    }

    private function scheduleExists(string $day, string $country, ?int $ignoreId = null): bool
    {
        $query = AmzAdsCampaignSchedule::where('day_of_week', $day)
            ->where('country', $country);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }
        return $query->exists();
    }

    private function prepareScheduleData(array $validated): array
    {
        // Attach today’s date for parsing
        $today = now()->toDateString();
        $start = Carbon::createFromFormat('Y-m-d H:i', $today . ' ' . $validated['start_time']);
        $end   = Carbon::createFromFormat('Y-m-d H:i', $today . ' ' . $validated['end_time']);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $hoursOn  = $start->floatDiffInHours($end);
        $hoursOff = 24 - $hoursOn;
        return array_merge($validated, [
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time'   => $end->format('Y-m-d H:i:s'),
            'hours_on'   => $hoursOn,
            'hours_off'  => $hoursOff,
            'added'      => now()->toDateString(),
        ]);
    }
}
