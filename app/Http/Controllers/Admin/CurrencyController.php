<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\CurrencyEnum;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CurrencyController extends Controller
{
    public function edit($id)
    {
        $this->authorize(CurrencyEnum::CurrencyUpdate);
        $currency = Currency::findOrFail($id);
        return view('pages.admin.data.exchange.form', compact('currency'));
    }

    public function update(Request $request, $id)
    {
        $this->authorize(CurrencyEnum::CurrencyUpdate);
        $validated = $request->validate([
            'currency_name'            => 'nullable|string|max:50',
            'currency_symbol'          => 'nullable|string|max:5',
            'conversion_rate_to_usd'   => 'required|numeric|min:0',
        ]);

        $currency = Currency::findOrFail($id);
        $currency->update($validated);
        Cache::forget('currency_rates_usd');

        return redirect()->route('admin.data.index')->with('success', 'Currency updated successfully.');
    }
}
