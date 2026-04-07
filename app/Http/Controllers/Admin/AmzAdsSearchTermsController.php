<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Http\Controllers\Controller;
use App\Exports\DownloadSpSearchTerms;
use App\Models\ProductAsins;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\SearchTermsTrait;

class AmzAdsSearchTermsController extends Controller
{
    use SearchTermsTrait;

    public function index(Request $request)
    {
        try {
            $this->authorize(AmzAdsEnum::AmazonAdsSearchTerms);

            $searchTerms = $this->getSearchTermsQuery($request)
                ->paginate($request->get('per_page', 25))
                ->appends($request->query());
            // dd($searchTerms->toArray());
            return view('pages.admin.amzAds.searchterms.index', compact('searchTerms'));
        } catch (\Exception $e) {
            Log::error('Error in searchTermsSp: ' . $e->getMessage());
            return back()->with(
                'error',
                'Something went wrong. Please try again.'
            );
        }
    }

    public function downloadSpSearchTerms(Request $request)
    {
        try {
            $this->authorize(AmzAdsEnum::AmazonAdsSearchTermsExport);
            $marketTz = config('timezone.market');
            $date = $request->input('date', Carbon::now($marketTz)->subDay()->toDateString());

            return Excel::download(new DownloadSpSearchTerms($request), "sp_search_terms_{$date}.xlsx");
        } catch (\Throwable $e) {

            Log::error('SP Search Terms download failed', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return back()->with('error', 'Unable to download Search Terms report. Please try again later.');
        }
    }

    public function productAsins(Request $request)
    {
        try {
            $query   = $request->get('q', '');
            $page    = max((int) $request->get('page', 1), 1);
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
        } catch (\Throwable $e) {

            Log::error('ASIN search failed', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'results'    => [],
                'pagination' => ['more' => false],
            ], 500);
        }
    }
}
