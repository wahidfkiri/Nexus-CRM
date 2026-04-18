<?php

namespace Vendor\Client\Imports;

use Vendor\Client\Models\Client;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

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
        return [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'type' => 'in:entreprise,particulier,startup',
            'status' => 'in:actif,inactif,en_attente',
        ];
    }
}