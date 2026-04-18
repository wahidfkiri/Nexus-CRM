@extends('layouts.app')

@section('title', 'Clients')

@section('content')
<div class="container-fluid p-4">
    <div class="page-header mb-4">
        <div>
            <h2><i class="fas fa-users me-2 text-primary"></i>Gestion des clients</h2>
            <p class="text-muted">Gérez votre portefeuille clients</p>
        </div>
        <div class="header-actions">
            @can('export_clients')
            <div class="btn-group">
                <button type="button" class="btn-export dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-download"></i> Exporter
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('clients.export.csv') }}"><i class="fas fa-file-csv"></i> CSV</a></li>
                    <li><a class="dropdown-item" href="{{ route('clients.export.excel') }}"><i class="fas fa-file-excel"></i> Excel</a></li>
                    <li><a class="dropdown-item" href="{{ route('clients.export.pdf') }}"><i class="fas fa-file-pdf"></i> PDF</a></li>
                </ul>
            </div>
            @endcan
            
            @can('create_clients')
            <a href="{{ route('clients.create') }}" class="btn-add-client">
                <i class="fas fa-plus"></i> Nouveau client
            </a>
            @endcan
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: #3b82f620; color: #3b82f6;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value" id="totalClients">0</div>
            <div class="stat-label">Clients totaux</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #10b98120; color: #10b981;">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-value" id="activeClients">0</div>
            <div class="stat-label">Clients actifs</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f59e0b20; color: #f59e0b;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-value" id="pendingClients">0</div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #8b5cf620; color: #8b5cf6;">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-value" id="totalRevenue">€0</div>
            <div class="stat-label">Chiffre d'affaires</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar mb-4">
        <div class="search-wrapper-table">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Rechercher...">
        </div>
        <div class="filter-group">
            <select id="typeFilter" class="filter-select">
                <option value="">Tous les types</option>
                <option value="entreprise">Entreprise</option>
                <option value="particulier">Particulier</option>
                <option value="startup">Startup</option>
            </select>
            <select id="statusFilter" class="filter-select">
                <option value="">Tous les statuts</option>
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
                <option value="en_attente">En attente</option>
            </select>
            <button id="applyFiltersBtn" class="btn-apply"><i class="fas fa-filter"></i> Appliquer</button>
            <button id="resetFiltersBtn" class="btn-reset"><i class="fas fa-undo"></i> Réinitialiser</button>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions-bar" id="bulkActionsBar" style="display:none;">
        <span id="selectedCount">0</span> client(s) sélectionné(s)
        <div class="bulk-buttons">
            <button class="btn-bulk-status" data-status="actif"><i class="fas fa-check-circle"></i> Activer</button>
            <button class="btn-bulk-status" data-status="inactif"><i class="fas fa-ban"></i> Désactiver</button>
            <button class="btn-bulk-delete"><i class="fas fa-trash"></i> Supprimer</button>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="clients-table-wrapper">
        <table class="clients-table" id="clientsTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Statut</th>
                    <th>CA</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="clientsTableBody">
                <tr><td colspan="8" class="text-center">Chargement...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="table-pagination mt-4">
        <div id="paginationInfo"></div>
        <div class="pagination-controls" id="paginationControls"></div>
    </div>
</div>

<!-- Import Modal -->
<div class="modal-overlay" id="importModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3><i class="fas fa-upload"></i> Importer des clients</h3>
            <button class="modal-close" id="closeImportModal">&times;</button>
        </div>
        <div class="modal-body">
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Fichier (CSV, Excel)</label>
                    <input type="file" name="file" class="form-control-modern" accept=".csv,.xlsx,.xls" required>
                    <small class="text-muted">Téléchargez le <a href="{{ route('clients.import.template') }}">template</a> pour respecter le format</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelImportBtn">Annuler</button>
            <button class="btn-save" id="confirmImportBtn">Importer</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let perPage = 15;

function loadClients() {
    const search = $('#searchInput').val();
    const type = $('#typeFilter').val();
    const status = $('#statusFilter').val();
    
    $.ajax({
        url: '{{ route("clients.data") }}',
        method: 'GET',
        data: { page: currentPage, per_page: perPage, search: search, type: type, status: status },
        success: function(response) {
            renderTable(response.data);
            updatePagination(response);
            updateStats();
        }
    });
}

function renderTable(clients) {
    const tbody = $('#clientsTableBody');
    tbody.empty();
    
    if(clients.length === 0) {
        tbody.html('<tr><td colspan="8" class="text-center">Aucun client trouvé</td></tr>');
        return;
    }
    
    clients.forEach(client => {
        const statusClass = client.status === 'actif' ? 'status-active' : client.status === 'inactif' ? 'status-inactif' : 'status-pending';
        const statusLabel = client.status === 'actif' ? 'Actif' : client.status === 'inactif' ? 'Inactif' : 'En attente';
        const typeClass = client.type === 'entreprise' ? 'badge-entreprise' : client.type === 'startup' ? 'badge-startup' : 'badge-particulier';
        const typeLabel = client.type === 'entreprise' ? 'Entreprise' : client.type === 'startup' ? 'Startup' : 'Particulier';
        
        tbody.append(`
            <tr>
                <td><input type="checkbox" class="client-checkbox" data-id="${client.id}"></td>
                <td>
                    <div class="client-info">
                        <div class="client-avatar">${client.initials || client.company_name.substring(0,2)}</div>
                        <div>
                            <div class="client-name">${client.company_name}</div>
                            <div class="client-email">${client.contact_name || ''}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge-client ${typeClass}">${typeLabel}</span></td>
                <td>${client.email}</td>
                <td>${client.phone || '-'}</td>
                <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                <td>${new Intl.NumberFormat('fr-FR', {style: 'currency', currency: 'EUR'}).format(client.revenue || 0)}</td>
                <td class="action-buttons">
                    <a href="/clients/${client.id}" class="action-btn action-view"><i class="fas fa-eye"></i></a>
                    <a href="/clients/${client.id}/edit" class="action-btn action-edit"><i class="fas fa-edit"></i></a>
                    <button class="action-btn action-delete" onclick="deleteClient(${client.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `);
    });
}

$(document).ready(function() {
    loadClients();
    $('#applyFiltersBtn').click(loadClients);
    $('#resetFiltersBtn').click(function() { $('#searchInput, #typeFilter, #statusFilter').val(''); loadClients(); });
});
</script>
@endpush