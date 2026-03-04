<?php


namespace App\Http\Controllers\Admin;


use App\Enum\Permissions\SellingEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Seller\SellingAsinService;

class SellingAsinController extends Controller
{
    protected SellingAsinService $service;

    public function __construct(SellingAsinService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $this->authorize(SellingEnum::SellingAsin);

        try {
            $data = $this->service->getAsinsForIndex($request);

            return view('pages.admin.sellingAsin.index', $data);
        } catch (\Throwable $e) {
            Log::error('Error fetching ASIN selling items: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Something went wrong while fetching selling items.');
        }
    }

    public function details(string $asin)
    {
        $this->authorize(SellingEnum::SellingDashboard);

        try {
            $data = $this->service->getAsinDetails($asin);

            return view('pages.admin.sellingAsin.show', $data);
        } catch (\Throwable $e) {
            Log::error('Error in SellingAsinController@details', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'An error occurred while loading the product details.');
        }
    }
}
