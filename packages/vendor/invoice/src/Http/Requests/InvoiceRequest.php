<?php

namespace Vendor\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Entête
            'client_id'             => 'required|exists:clients,id',
            'quote_id'              => 'nullable|exists:quotes,id',
            'stock_order_id'        => 'nullable|exists:stock_orders,id',
            'reference'             => 'nullable|string|max:100',
            'status'                => 'sometimes|in:draft,sent,viewed,partial,paid,overdue,cancelled,refunded',
            'currency'              => 'required|string|size:3',
            'exchange_rate'         => 'nullable|numeric|min:0.000001',

            // Dates
            'issue_date'            => 'required|date',
            'due_date'              => 'nullable|date|after_or_equal:issue_date',

            // Paiement
            'payment_terms'         => 'nullable|integer|min:0|max:365',
            'payment_method'        => 'nullable|string|max:50',

            // Remise globale
            'discount_type'         => 'nullable|in:none,percent,fixed',
            'discount_value'        => 'nullable|numeric|min:0',

            // Taxes
            'tax_rate'              => 'nullable|numeric|min:0|max:100',
            'withholding_tax_rate'  => 'nullable|numeric|min:0|max:100',

            // Textes
            'notes'                 => 'nullable|string|max:5000',
            'terms'                 => 'nullable|string|max:5000',
            'footer'                => 'nullable|string|max:1000',
            'internal_notes'        => 'nullable|string|max:5000',

            // Lignes
            'items'                 => 'required|array|min:1',
            'items.*.description'   => 'required|string|max:1000',
            'items.*.article_id'    => 'nullable|exists:stock_articles,id',
            'items.*.reference'     => 'nullable|string|max:100',
            'items.*.quantity'      => 'required|numeric|min:0.0001',
            'items.*.unit'          => 'nullable|string|max:30',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:none,percent,fixed',
            'items.*.discount_value'=> 'nullable|numeric|min:0',
            'items.*.tax_rate'      => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id'           => 'client',
            'issue_date'          => 'date d\'émission',
            'due_date'            => 'date d\'échéance',
            'items'               => 'lignes',
            'items.*.description' => 'description de la ligne',
            'items.*.quantity'    => 'quantité',
            'items.*.unit_price'  => 'prix unitaire',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required'          => 'Le client est obligatoire.',
            'client_id.exists'            => 'Le client sélectionné n\'existe pas.',
            'issue_date.required'         => 'La date d\'émission est obligatoire.',
            'due_date.after_or_equal'     => 'La date d\'échéance doit être après la date d\'émission.',
            'items.required'              => 'Au moins une ligne est obligatoire.',
            'items.min'                   => 'Au moins une ligne est obligatoire.',
            'items.*.description.required'=> 'La description de chaque ligne est obligatoire.',
            'items.*.quantity.required'   => 'La quantité de chaque ligne est obligatoire.',
            'items.*.quantity.min'        => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.required' => 'Le prix unitaire de chaque ligne est obligatoire.',
        ];
    }
}
