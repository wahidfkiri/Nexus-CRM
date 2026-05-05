<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Inscription | NexusCRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="register-page">
    <div class="register-shell">
        <div class="register-backdrop" aria-hidden="true">
            <div class="register-glow register-glow-a"></div>
            <div class="register-glow register-glow-b"></div>
            <div class="register-grid"></div>
            <div class="register-app-cloud">
                @foreach(($loginApps ?? []) as $app)
                    <span
                        class="register-app-mark"
                        style="--x: {{ $app['x'] }}%; --y: {{ $app['y'] }}%; --size: {{ $app['size'] }}px; --delay: {{ $app['delay'] }}s; --drift: {{ $app['drift'] }}s; --accent: {{ $app['color'] }};"
                        title="{{ $app['name'] }}"
                    >
                        <span class="register-app-mark-core">
                            @if(!empty($app['icon_url']))
                                <img src="{{ $app['icon_url'] }}" alt="{{ $app['name'] }}">
                            @else
                                <i class="{{ $app['icon_class'] }}"></i>
                            @endif
                        </span>
                    </span>
                @endforeach
            </div>
        </div>

        <main class="register-stage">
            <section class="register-card" aria-labelledby="registerTitle">
                <div class="register-brand-row">
                    <div class="register-brand-badge">
                        <span class="register-brand-icon"><i class="fas fa-chart-line"></i></span>
                        <span class="register-brand-name">NexusCRM</span>
                    </div>
                </div>

                <div class="register-copy">
                    <p class="register-eyebrow">Demarrage rapide</p>
                    <h1 id="registerTitle">Creer votre compte</h1>
                    <p class="register-description">
                        Lancez votre espace CRM en quelques minutes et retrouvez vos integrations dans une interface claire et fluide.
                    </p>
                </div>

                @php
                    $initialFeedbackType = $errors->any() || session('error') ? 'error' : (session('success') ? 'success' : '');
                    $initialFeedbackMessage = $errors->any()
                        ? $errors->first()
                        : (session('error') ?: (session('success') ?: ''));
                @endphp

                <div
                    id="registerFeedback"
                    class="register-feedback {{ $initialFeedbackType ? 'is-visible is-' . $initialFeedbackType : '' }}"
                    @if($initialFeedbackType)
                        data-initial-type="{{ $initialFeedbackType }}"
                        data-initial-message="{{ $initialFeedbackMessage }}"
                    @endif
                >
                    <span class="register-feedback-icon" id="registerFeedbackIcon">
                        <i class="fas {{ $initialFeedbackType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                    </span>
                    <span id="registerFeedbackText">{{ $initialFeedbackMessage }}</span>
                </div>

                <form method="POST" action="{{ route('register') }}" id="registerForm" class="register-form" data-secure-form="1" novalidate>
                    @csrf

                    <div class="register-grid-fields register-grid-fields-two">
                        <div class="register-field">
                            <label for="firstName" class="register-label">Prenom</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-user"></i></span>
                                <input
                                    type="text"
                                    class="form-control-modern @error('first_name') is-invalid @enderror"
                                    id="firstName"
                                    name="first_name"
                                    value="{{ old('first_name') }}"
                                    placeholder="Votre prenom"
                                    required
                                    autocomplete="given-name"
                                >
                            </div>
                            @error('first_name')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="register-field">
                            <label for="lastName" class="register-label">Nom</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-user"></i></span>
                                <input
                                    type="text"
                                    class="form-control-modern @error('last_name') is-invalid @enderror"
                                    id="lastName"
                                    name="last_name"
                                    value="{{ old('last_name') }}"
                                    placeholder="Votre nom"
                                    required
                                    autocomplete="family-name"
                                >
                            </div>
                            @error('last_name')<span class="form-error">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="register-field">
                        <label for="email" class="register-label">Email professionnel</label>
                        <div class="register-input-wrap">
                            <span class="register-input-icon"><i class="fas fa-envelope"></i></span>
                            <input
                                type="email"
                                class="form-control-modern @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nom@entreprise.com"
                                required
                                autocomplete="email"
                            >
                        </div>
                        @error('email')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="register-grid-fields register-grid-fields-two">
                        <div class="register-field">
                            <label for="password" class="register-label">Mot de passe</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control-modern @error('password') is-invalid @enderror"
                                    id="password"
                                    name="password"
                                    placeholder="Choisissez un mot de passe"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" data-target="password" aria-label="Afficher ou masquer le mot de passe">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            @error('password')<span class="form-error">{{ $message }}</span>@enderror
                        </div>

                        <div class="register-field">
                            <label for="confirmPassword" class="register-label">Confirmation</label>
                            <div class="register-input-wrap">
                                <span class="register-input-icon"><i class="fas fa-lock"></i></span>
                                <input
                                    type="password"
                                    class="form-control-modern"
                                    id="confirmPassword"
                                    name="password_confirmation"
                                    placeholder="Confirmez le mot de passe"
                                    required
                                    autocomplete="new-password"
                                >
                                <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Afficher ou masquer le mot de passe">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="register-strength" id="passwordStrength" aria-live="polite">
                        <div class="register-strength-bar">
                            <span class="register-strength-progress" id="strengthProgress"></span>
                        </div>
                        <span class="register-strength-text" id="strengthText"></span>
                    </div>

                    <label class="register-checkbox">
                        <input type="checkbox" id="termsCheckbox" required>
                        <span class="register-checkbox-mark"></span>
                        <span>J'accepte les conditions d'utilisation et la politique de confidentialite.</span>
                    </label>

                    <button type="submit" class="btn-register" id="registerBtn">
                        <span class="btn-register-label">Creer mon compte</span>
                        <span class="btn-register-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="register-divider"><span>ou</span></div>

                <a href="{{ route('auth.google.redirect') }}" class="register-google-btn">
                    <i class="fab fa-google"></i>
                    <span>Continuer avec Google</span>
                </a>

                <div class="register-footer">
                    <span>Vous avez deja un compte ?</span>
                    <a href="{{ route('login') }}">Se connecter</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.RegisterPage = {
            defaultRedirect: @json(route('login')),
            registerErrorMessage: 'Inscription impossible pour le moment. Reessayez dans un instant.'
        };
    </script>
    @include('layouts.partials.tauri-bridge')
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    <script src="{{ asset('js/register.js') }}"></script>
</body>
</html>
