<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseOrderRequest extends FormRequest
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
            'order_number'      => 'required|string|max:100',
            'supplier_id'       => 'required|integer|exists:users,id',
            'warehouse_id'      => 'required|integer|exists:warehouses,id',
            'order_date'        => 'required|date',
            'expected_arrival'  => 'nullable|date|after_or_equal:order_date',
            'status'            => 'required|in:draft,confirmed,cancelled,received', 
            'payment_terms'     => 'nullable|string|max:255',
            'shipping_method'   => 'nullable|string|max:255',
            'notes'             => 'nullable|string|max:1000',
        ];
    }
}
