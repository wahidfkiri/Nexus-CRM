@extends('layouts.app')

@section('title', 'Modifier client')

@section('content')
<div class="container-fluid p-4">
    <div class="page-header mb-4">
        <div>
            <h2><i class="fas fa-edit me-2 text-primary"></i>Modifier le client</h2>
            <p class="text-muted">Modifiez les informations du client</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn-cancel-page">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('clients.update', $client) }}" id="clientForm">
            @csrf
            @method('PUT')
            
            <div class="form-section">
                <h3><i class="fas fa-building"></i> Informations générales</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nom de l'entreprise <span class="required">*</span></label>
                            <input type="text" name="company_name" class="form-control-modern @error('company_name') error @enderror" value="{{ old('company_name', $client->company_name) }}" required>
                            @error('company_name')<span class="error-text">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" class="form-control-modern @error('email') error @enderror" value="{{ old('email', $client->email) }}" required>
                            @error('email')<span class="error-text">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Personne de contact</label>
                            <input type="text" name="contact_name" class="form-control-modern" value="{{ old('contact_name', $client->contact_name) }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="tel" name="phone" class="form-control-modern" value="{{ old('phone', $client->phone) }}">
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
                            <input type="text" name="address" class="form-control-modern" value="{{ old('address', $client->address) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Ville</label>
                            <input type="text" name="city" class="form-control-modern" value="{{ old('city', $client->city) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Code postal</label>
                            <input type="text" name="postal_code" class="form-control-modern" value="{{ old('postal_code', $client->postal_code) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Pays</label>
                            <input type="text" name="country" class="form-control-modern" value="{{ old('country', $client->country) }}">
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
                                <option value="entreprise" {{ old('type', $client->type) == 'entreprise' ? 'selected' : '' }}>Entreprise</option>
                                <option value="particulier" {{ old('type', $client->type) == 'particulier' ? 'selected' : '' }}>Particulier</option>
                                <option value="startup" {{ old('type', $client->type) == 'startup' ? 'selected' : '' }}>Startup</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Statut <span class="required">*</span></label>
                            <select name="status" class="form-control-modern" required>
                                <option value="actif" {{ old('status', $client->status) == 'actif' ? 'selected' : '' }}>Actif</option>
                                <option value="inactif" {{ old('status', $client->status) == 'inactif' ? 'selected' : '' }}>Inactif</option>
                                <option value="en_attente" {{ old('status', $client->status) == 'en_attente' ? 'selected' : '' }}>En attente</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Source</label>
                            <select name="source" class="form-control-modern">
                                <option value="direct" {{ old('source', $client->source) == 'direct' ? 'selected' : '' }}>Direct</option>
                                <option value="site_web" {{ old('source', $client->source) == 'site_web' ? 'selected' : '' }}>Site web</option>
                                <option value="reference" {{ old('source', $client->source) == 'reference' ? 'selected' : '' }}>Recommandation</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-sticky-note"></i> Notes</h3>
                <div class="form-group">
                    <textarea name="notes" class="form-control-modern" rows="4">{{ old('notes', $client->notes) }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="window.history.back()">Annuler</button>
                <button type="submit" class="btn-save">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>
@endsection