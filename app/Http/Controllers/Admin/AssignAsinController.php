<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAsins;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use App\Models\UserAssignedAsin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enum\Permissions\UserEnum;
use App\Imports\UserAsinImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AllAsinExport;
use Maatwebsite\Excel\Validators\ValidationException;
class AssignAsinController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(UserEnum::UserAssignAsin);
        $query = User::with('roles')
            ->whereNotIn('id', [auth()->id()])
            ->whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['administrator', 'developer']);
            });
        // Filter by dropdown
        if ($request->filled('filter')) {
            if ($request->filter === 'reporting') {
                $query->where('reporting_to', auth()->id());
            }
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }
        $users = $query->paginate($request->input('per_page', 10));
        return view('pages.admin.user.assignAsin.index', compact('users'));
    }

    public function assignAsin($id)
    {
        $this->authorize(UserEnum::UserAssignAsin);
        try {
            $user          = User::where('id', $id)->select('id', 'name')->first();
            $assignedAsins = UserAssignedAsin::where('user_id',  $user->id)
                ->pluck('asin')
                ->toArray();

            return view('pages.admin.user.assignAsin.form', compact('user', 'assignedAsins'));
        } catch (Exception $e) {
            Log::error("💥 Error ASIN Assign page" . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('error', 'Something went wrong,Try again later');
        }
    }

    public function search(Request $request)
    {
        $query   = $request->input('q', '');
        $page    = max((int) $request->input('page', 1), 1);
        $perPage = 20;
        $asinsQuery = ProductAsins::query()
            ->select('asin1 as text')
            ->distinct();

        if ($query) {
            $asinsQuery->where('asin1', 'like', "%{$query}%");
        }
        $results = $asinsQuery
            ->orderBy('text')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        $more = $results->count() > $perPage;
        $results = $results->take($perPage)->map(fn($asin) => [
            'id'   => $asin->text,
            'text' => $asin->text
        ]);
        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $more]
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize(UserEnum::UserAssignAsin);
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'asins'   => 'nullable|array',
            'asins.*' => 'string|max:50',
        ]);

        $userId = $request->user_id;
        $asins  = $request->asins ?? [];
        $user   = Auth::user()->id;
        // Prepare data for upsert
        $data = collect($asins)->map(function ($asin) use ($userId, $user) {
            return [
                'user_id'        => $userId,
                'asin'           => $asin,
                'sku'            => null,
                'assigned_by_id' => $user,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        })->toArray();

        // Upsert to insert/update ASINs
        UserAssignedAsin::upsert(
            $data,
            ['user_id', 'asin'],
            ['sku', 'assigned_by_id', 'updated_at']
        );

        // Delete ASINs that were removed in the form (not in submitted list)
        UserAssignedAsin::where('user_id', $userId)
            ->whereNotIn('asin', $asins)
            ->delete();

        return redirect()->back()->with('success', 'ASINs updated successfully.');
    }
    public function exampleExport(){
        return Excel::download(new AllAsinExport, 'all-asins.xlsx');
    }
    public function import(Request $request)
    {
        $this->authorize(UserEnum::UserAssignAsin);
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'file'    => 'required|mimes:xlsx,xls|max:2048',
        ]);
        $userId       = $request->user_id;
        $assignedById = Auth::id();

        try {
            Excel::import(new UserAsinImport($userId, $assignedById), $request->file('file'));
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors([
                'file' => 'The uploaded Excel file contains invalid data format.',
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors([
                'file' => 'Something went wrong while processing the file. Please check and try again.',
            ]);
        }
        return redirect()->back()->with('success', 'ASINs updated successfully.');
    }
}
