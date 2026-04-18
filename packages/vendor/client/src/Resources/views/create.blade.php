@extends('layouts.app')

@section('title', 'Nouveau client')

@section('content')
<div class="container-fluid p-4">
    <div class="page-header mb-4">
        <div>
            <h2><i class="fas fa-user-plus me-2 text-primary"></i>Nouveau client</h2>
            <p class="text-muted">Ajoutez un nouveau client à votre portefeuille</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn-cancel-page">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('clients.store') }}" id="clientForm">
            @csrf
            
            <div id="alert-container"></div>

            <div class="form-section">
                <h3><i class="fas fa-building"></i> Informations générales</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nom de l'entreprise <span class="required">*</span></label>
                            <input type="text" name="company_name" class="form-control-modern" value="{{ old('company_name') }}" required>
                            <span class="error-text error-company_name"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control-modern" value="{{ old('email') }}" required>
                            <span class="error-text error-email"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Personne de contact</label>
                            <input type="text" name="contact_name" class="form-control-modern" value="{{ old('contact_name') }}">
                            <span class="error-text error-contact_name"></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="phone" class="form-control-modern" value="{{ old('phone') }}">
                            <span class="error-text error-phone"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-map-marker-alt"></i> Adresse</h3>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Adresse</label>
                            <input type="text" name="address" class="form-control-modern" value="{{ old('address') }}">
                            <span class="error-text error-address"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" name="city" class="form-control-modern" value="{{ old('city') }}">
                            <span class="error-text error-city"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Code postal</label>
                            <input type="text" name="postal_code" class="form-control-modern" value="{{ old('postal_code') }}">
                            <span class="error-text error-postal_code"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Pays</label>
                            <input type="text" name="country" class="form-control-modern" value="{{ old('country') }}">
                            <span class="error-text error-country"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-tag"></i> Catégorisation</h3>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Type <span class="required">*</span></label>
                            <select name="type" class="form-control-modern" required>
                                <option value="entreprise" {{ old('type') == 'entreprise' ? 'selected' : '' }}>Entreprise</option>
                                <option value="particulier" {{ old('type') == 'particulier' ? 'selected' : '' }}>Particulier</option>
                                <option value="startup" {{ old('type') == 'startup' ? 'selected' : '' }}>Startup</option>
                            </select>
                            <span class="error-text error-type"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Statut <span class="required">*</span></label>
                            <select name="status" class="form-control-modern" required>
                                <option value="actif" {{ old('status') == 'actif' ? 'selected' : '' }}>Actif</option>
                                <option value="inactif" {{ old('status') == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                <option value="en_attente" {{ old('status') == 'en_attente' ? 'selected' : '' }}>En attente</option>
                            </select>
                            <span class="error-text error-status"></span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Source</label>
                            <select name="source" class="form-control-modern">
                                <option value="direct" {{ old('source') == 'direct' ? 'selected' : '' }}>Direct</option>
                                <option value="site_web" {{ old('source') == 'site_web' ? 'selected' : '' }}>Site web</option>
                                <option value="reference" {{ old('source') == 'reference' ? 'selected' : '' }}>Recommandation</option>
                            </select>
                            <span class="error-text error-source"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                <div class="form-group">
                    <textarea name="notes" class="form-control-modern" rows="4">{{ old('notes') }}</textarea>
                    <span class="error-text error-notes"></span>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="window.history.back()">Annuler</button>
                <button type="submit" class="btn-save" id="submitBtn">
                    <i class="fas fa-save"></i> Créer le client
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#clientForm').on('submit', function(e) {
        e.preventDefault();
        
        // Reset errors
        $('.error-text').text('');
        $('.form-control-modern').removeClass('is-invalid');
        
        // Disable submit button
        const $submitBtn = $('#submitBtn');
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true);
        $submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Création en cours...');
        
        // Clear previous alerts
        $('#alert-container').empty();
        
        // Get form data
        const formData = new FormData(this);
        
        // AJAX request
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Show success message
                $('#alert-container').html(`
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ${response.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                
                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = response.redirect;
                }, 2000);
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    
                    // Display errors for each field
                    for (const field in errors) {
                        const errorMessage = errors[field][0];
                        $(`.error-${field}`).text(errorMessage);
                        $(`[name="${field}"]`).addClass('is-invalid');
                    }
                    
                    // Show general alert
                    $('#alert-container').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Veuillez corriger les erreurs ci-dessous.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 500);
                } else if (xhr.status === 500) {
                    // Server error
                    $('#alert-container').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> Une erreur est survenue. Veuillez réessayer.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                } else {
                    // Other errors
                    const errorMsg = xhr.responseJSON?.message || 'Une erreur est survenue';
                    $('#alert-container').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> ${errorMsg}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                }
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false);
                $submitBtn.html(originalText);
            }
        });
    });
});
</script>

<style>
.is-invalid {
    border-color: #dc3545 !important;
    background-color: #fff8f8 !important;
}

.error-text {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
}

.alert {
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 0.5rem;
}

.alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.btn-save:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
@endpush
@endsection