<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-container delete-modal">
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Supprimer le client</h3>
        <p>Êtes-vous sûr de vouloir supprimer ce client ? Cette action est irréversible.</p>
        <div class="modal-footer">
            <button class="btn-cancel" id="cancelDeleteBtn">Annuler</button>
            <button class="btn-danger" id="confirmDeleteBtn">Supprimer</button>
        </div>
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