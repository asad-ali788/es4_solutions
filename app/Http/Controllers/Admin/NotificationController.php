<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\NotificationEnum;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request, $id = null)
    {
        $this->authorize(NotificationEnum::Notification);

        $user   = Auth::user();
        $status = $request->get('status');
        $search = $request->get('search');

        // Base notification query scoped to role
        $baseQuery = Notification::query();
        if (!$user->hasRole(['administrator', 'developer'])) {
            $baseQuery->where('assigned_user_id', $user->id);
        }

        $notification_details = null;
        $notification         = null;
        $users                = null;
        $details_table        = null;

        // Handle details view
        if ($id) {
            $notification_details = $baseQuery->withTrashed()->with(['handlerUser', 'details'])->findOrFail($id);
            $users                = User::all();
            $detailSearch         = $request->get('detail_search');
            $detailsQuery         = $notification_details->details();

            if ($detailSearch) {
                $detailsQuery->where('sku', 'like', "%{$detailSearch}%");
            }
            $details_table = $detailsQuery->paginate($request->get('per_page', 15), ['*'], 'detail_page')->fragment('notification_details');
        } else {
            // Filter by read/unread status
            if ($status === 'read') {
                $baseQuery->where('read_status', 1);
            } elseif ($status === 'unread') {
                $baseQuery->where('read_status', 0);
            } elseif ($status === 'trashed') {
                $baseQuery->onlyTrashed();
            }

            // Handle search input
            if ($search) {
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('notification_id', 'like', "%{$search}%");
                    // Try date parsing from 'd M Y'
                    try {
                        $parsedDate = Carbon::createFromFormat('d M Y', $search)->format('Y-m-d');
                        $q->orWhereDate('created_date', $parsedDate);
                    } catch (\Exception $e) {
                        // Ignore date parsing errors
                    }
                });
            }

            $notification = $baseQuery->orderByDesc('created_date')->paginate($request->get('per_page', 15))->fragment('notification')->appends($request->query());
        }

        // Unread Count scoped by role
        $unreadCountQuery = Notification::query()->where('read_status', 0);
        if (!$user->hasRole(['administrator', 'developer'])) {
            $unreadCountQuery->where('assigned_user_id', $user->id);
        }
        $unreadCount = $unreadCountQuery->count();

        return view('pages.admin.notification.index', compact(
            'notification',
            'unreadCount',
            'notification_details',
            'users',
            'details_table'
        ));
    }

    public function toggleStatus($id)
    {
        $notification = Notification::findOrFail($id);

        if ($notification->read_status == 0) {
            $notification->read_status = 1;
            $notification->read_date = now();
        } else {
            $notification->read_status = 0;
            $notification->read_date = null;
        }
        $notification->handler = Auth::user()->id;
        $notification->save();

        return redirect()->back()->with('success', 'Notification status updated successfully.');
    }

    public function assignUser(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        $assignedId = $request->input('assigned_user_id');
        $notification->assigned_user_id = $assignedId == 0 ? null : $assignedId;
        $notification->handler = Auth::user()->id;
        $notification->save();

        return redirect()->back()->with('success', 'Notification updated successfully.');
    }

    public function destroy($id)
    {
        $this->authorize(NotificationEnum::NotificationTrash);

        $notification = Notification::findOrFail($id);
        $notification->delete();
        return redirect()->back()->with('success', 'Notification Deleted successfully.');
    }

    /**
     * Clear the user's notification cache.
     */
    public function clearCache(Request $request)
    {
        try {
            if ($user = Auth::user()) {
                Cache::forget('notifications:' . $user->id);
            }
            return redirect()->back()->with('success', 'Notification cache refreshed!');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Failed to refresh notifications.');
        }
    }
}
