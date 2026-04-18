<?php

namespace Vendor\Invoice\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Vendor\Invoice\Models\Invoice;
use Vendor\Invoice\Models\InvoiceItem;
use Vendor\Client\Models\Client;
use Illuminate\Support\Facades\DB;

class InvoicesImport implements ToCollection, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors  = [];

    public function chunkSize(): int { return config('invoice.import.chunk_size', 250); }

    public function collection(Collection $rows): void
    {
        $tenantId = auth()->user()->tenant_id;
        $userId   = auth()->id();

        foreach ($rows as $index => $row) {
            try {
                $client = Client::where('tenant_id', $tenantId)
                    ->where(function ($q) use ($row) {
                        $q->where('company_name', $row['client'] ?? '')
                          ->orWhere('email', $row['email_client'] ?? '');
                    })->first();

                if (!$client) {
                    $this->errors[] = "Ligne " . ($index + 2) . " : client introuvable.";
                    $this->skipped++;
                    continue;
                }

                DB::transaction(function () use ($row, $client, $tenantId, $userId) {
                    $invoice = Invoice::create([
                        'tenant_id'    => $tenantId,
                        'user_id'      => $userId,
                        'client_id'    => $client->id,
                        'number'       => $row['numero'] ?? null,
                        'reference'    => $row['reference'] ?? null,
                        'status'       => $row['statut'] ?? 'draft',
                        'currency'     => strtoupper($row['devise'] ?? 'EUR'),
                        'issue_date'   => $row['date_emission'] ?? now()->toDateString(),
                        'due_date'     => $row['echeance'] ?? null,
                        'payment_terms'=> (int)($row['conditions_paiement'] ?? 30),
                        'total'        => (float)($row['total_ttc'] ?? 0),
                        'amount_due'   => (float)($row['reste_du'] ?? $row['total_ttc'] ?? 0),
                        'notes'        => $row['notes'] ?? null,
                    ]);

                    // Ligne générique si import simple
                    if (!empty($row['description_ligne'])) {
                        InvoiceItem::create([
                            'invoice_id'  => $invoice->id,
                            'position'    => 0,
                            'description' => $row['description_ligne'],
                            'quantity'    => 1,
                            'unit_price'  => (float)($row['total_ht'] ?? $row['total_ttc'] ?? 0),
                            'total'       => (float)($row['total_ht'] ?? $row['total_ttc'] ?? 0),
                        ]);
                    }
                });

                $this->imported++;

            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($index + 2) . " : " . $e->getMessage();
                $this->skipped++;
            }
        }
    }
}
