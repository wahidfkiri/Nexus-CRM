<?php

namespace Vendor\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'             => 'required|exists:clients,id',
            'reference'             => 'nullable|string|max:100',
            'currency'              => 'required|string|size:3',
            'exchange_rate'         => 'nullable|numeric|min:0.000001',
            'issue_date'            => 'required|date',
            'valid_until'           => 'nullable|date|after_or_equal:issue_date',
            'discount_type'         => 'nullable|in:none,percent,fixed',
            'discount_value'        => 'nullable|numeric|min:0',
            'tax_rate'              => 'nullable|numeric|min:0|max:100',
            'withholding_tax_rate'  => 'nullable|numeric|min:0|max:100',
            'notes'                 => 'nullable|string|max:5000',
            'terms'                 => 'nullable|string|max:5000',
            'footer'                => 'nullable|string|max:1000',
            'internal_notes'        => 'nullable|string|max:5000',
            'items'                 => 'required|array|min:1',
            'items.*.description'   => 'required|string|max:1000',
            'items.*.reference'     => 'nullable|string|max:100',
            'items.*.quantity'      => 'required|numeric|min:0.0001',
            'items.*.unit'          => 'nullable|string|max:30',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:none,percent,fixed',
            'items.*.discount_value'=> 'nullable|numeric|min:0',
            'items.*.tax_rate'      => 'nullable|numeric|min:0|max:100',
        ];
    }
}
