<?php

namespace Vendor\User\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->with('roles')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Nom', 'Email', 'Téléphone',
            'Rôle', 'Statut', 'Titre', 'Département',
            'Dernière connexion', 'Créé le',
        ];
    }

    public function map($user): array
    {
        $roleLabels = config('user.tenant_roles', []);
        $statusLabels = config('user.user_statuses', []);

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->phone ?? '—',
            $roleLabels[$user->role_in_tenant] ?? $user->role_in_tenant,
            $statusLabels[$user->status] ?? $user->status,
            $user->job_title ?? '—',
            $user->department ?? '—',
            $user->last_login_at?->format('d/m/Y H:i') ?? 'Jamais',
            $user->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}