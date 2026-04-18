<?php

namespace Vendor\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'         => 'required|numeric|min:0.01',
            'currency'       => 'required|string|size:3',
            'exchange_rate'  => 'nullable|numeric|min:0.000001',
            'payment_date'   => 'required|date',
            'payment_method' => 'required|string|in:' . implode(',', array_keys(config('invoice.payment_methods', []))),
            'reference'      => 'nullable|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'bank_account'   => 'nullable|string|max:50',
            'notes'          => 'nullable|string|max:2000',
            'attachment'     => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required'         => 'Le montant est obligatoire.',
            'amount.min'              => 'Le montant doit être supérieur à 0.',
            'payment_date.required'   => 'La date de paiement est obligatoire.',
            'payment_method.required' => 'Le mode de paiement est obligatoire.',
        ];
    }
}
