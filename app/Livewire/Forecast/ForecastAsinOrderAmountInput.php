<?php

namespace App\Livewire\Forecast;


use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\OrderForecastSnapshotAsins;

class ForecastAsinOrderAmountInput extends Component
{
    use AuthorizesRequests;

    public int $rowId;
    public $order_amount;

    public function mount(int $rowId, $orderAmount = null)
    {
        $this->rowId        = $rowId;
        $this->order_amount = $orderAmount;
    }

    public function updatedOrderAmount($value)
    {

        $this->validateOnly('order_amount', [
            'order_amount' => 'nullable|numeric|min:0|max:999999',
        ], [
            'order_amount.numeric' => 'Invalid number.',
            'order_amount.min'     => 'Cannot be negative.',
            'order_amount.max'     => 'Too large.',
        ]);

        // Convert empty → null
        $finalValue = ($value === '' || $value === null)
            ? null
            : (float) $value;

        // Update DB
        OrderForecastSnapshotAsins::where('id', $this->rowId)
            ->update([
                'order_amount' => $finalValue,
                'run_status'   => 'pending',
            ]);

        // Toast message
        $message = $finalValue === null
            ? "Order amount cleared"
            : "Order amount updated to $finalValue";

        $this->dispatch(
            'show-toast',
            type: 'success',
            message: $message
        );
    }

    public function render()
    {
        return view('livewire.forecast.forecast-asin-order-amount-input');
    }
}
