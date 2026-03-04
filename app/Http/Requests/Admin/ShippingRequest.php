<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ShippingRequest extends FormRequest
{ 
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipment_name'    => 'required|string|max:100',
            'tracking_number'  => 'nullable|string|max:100',
            'carrier_name'     => 'nullable|string|max:100',
            'dispatch_date'    => 'nullable|date',
            'expected_arrival' => 'nullable|date',
            'actual_arrival'   => 'nullable|date',
            'supplier_id'      => 'required|exists:users,id',
            'warehouse_id'     => 'required|exists:warehouses,id',
            'status'           => 'required|in:planned,shipped,in_transit,received,cancelled',
            'shipping_notes'   => 'nullable|string',
            'excel_file'       => 'nullable|file|mimes:xlsx,xls,csv|max:4048',
        ];
    }
}
