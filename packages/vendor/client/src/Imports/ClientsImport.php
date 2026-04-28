<?php

namespace Vendor\Client\Imports;

use Vendor\Client\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClientsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        return new Client([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'company_name' => $row['company_name'],
            'contact_name' => $row['contact_name'] ?? null,
            'email' => $row['email'],
            'phone' => $row['phone'] ?? null,
            'type' => $row['type'] ?? 'entreprise',
            'status' => $row['status'] ?? 'actif',
            'source' => $row['source'] ?? 'direct',
            'city' => $row['city'] ?? null,
            'postal_code' => $row['postal_code'] ?? null,
            'country' => $row['country'] ?? null,
        ]);
    }

    public function rules(): array
    {
        $tenantId = (int) (Auth::user()->tenant_id ?? 0);

        return [
            'company_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('clients', 'email')->where(function ($query) use ($tenantId) {
                    return $query
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at');
                }),
            ],
            'type' => 'in:entreprise,particulier,startup',
            'status' => 'in:actif,inactif,en_attente',
        ];
    }
}
