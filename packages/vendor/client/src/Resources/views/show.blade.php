@extends('layouts.app')

@section('title', 'Détails client')

@section('content')
<div class="container-fluid p-4">
    <div class="page-header mb-4">
        <div>
            <h2><i class="fas fa-user me-2 text-primary"></i>{{ $client->company_name }}</h2>
            <p class="text-muted">Détails du client</p>
        </div>
        <div>
            <a href="{{ route('clients.edit', $client) }}" class="btn-add-client">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="{{ route('clients.index') }}" class="btn-cancel-page">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-building"></i>
                    <h3>Informations générales</h3>
                </div>
                <div class="info-content">
                    <p><strong>Nom:</strong> {{ $client->company_name }}</p>
                    <p><strong>Contact:</strong> {{ $client->contact_name ?? '-' }}</p>
                    <p><strong>Email:</strong> {{ $client->email }}</p>
                    <p><strong>Téléphone:</strong> {{ $client->phone ?? '-' }}</p>
                    <p><strong>Type:</strong> <span class="badge-client badge-{{ $client->type }}">{{ $client->type_label }}</span></p>
                    <p><strong>Statut:</strong> <span class="status-badge status-{{ $client->status }}">{{ $client->status_label }}</span></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Adresse</h3>
                </div>
                <div class="info-content">
                    <p>{{ $client->address ?? '-' }}</p>
                    <p>{{ $client->postal_code }} {{ $client->city }}</p>
                    <p>{{ $client->country ?? '-' }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>Finances</h3>
                </div>
                <div class="info-content">
                    <p><strong>CA annuel:</strong> {{ number_format($client->revenue ?? 0, 2, ',', ' ') }} €</p>
                    <p><strong>Potentiel:</strong> {{ number_format($client->potential_value ?? 0, 2, ',', ' ') }} €</p>
                    <p><strong>Source:</strong> {{ $client->source_label ?? '-' }}</p>
                    <p><strong>Date création:</strong> {{ $client->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    @if($client->notes)
    <div class="info-card mt-4">
        <div class="info-header">
            <i class="fas fa-sticky-note"></i>
            <h3>Notes</h3>
        </div>
        <div class="info-content">
            <p>{{ $client->notes }}</p>
        </div>
    </div>
    @endif
</div>
@endsection