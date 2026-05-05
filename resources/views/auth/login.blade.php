<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Connexion | NexusCRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="login-backdrop" aria-hidden="true">
            <div class="login-glow login-glow-a"></div>
            <div class="login-glow login-glow-b"></div>
            <div class="login-grid"></div>
            <div class="login-app-cloud">
                @foreach(($loginApps ?? []) as $app)
                    <span
                        class="login-app-mark"
                        style="--x: {{ $app['x'] }}%; --y: {{ $app['y'] }}%; --size: {{ $app['size'] }}px; --delay: {{ $app['delay'] }}s; --drift: {{ $app['drift'] }}s; --accent: {{ $app['color'] }};"
                        title="{{ $app['name'] }}"
                    >
                        <span class="login-app-mark-core">
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

        <main class="login-stage">
            <section class="login-card" aria-labelledby="loginTitle">
                <div class="login-brand-row">
                    <div class="login-brand-badge">
                        <span class="login-brand-icon"><i class="fas fa-chart-line"></i></span>
                        <span class="login-brand-name">NexusCRM</span>
                    </div>
                </div>

                <div class="login-copy">
                    <p class="login-eyebrow">Acces securise</p>
                    <h1 id="loginTitle">Connexion</h1>
                    <p class="login-description">
                        Retrouvez votre espace CRM, vos extensions et vos automatisations depuis une interface simple et rapide.
                    </p>
                </div>

                @php
                    $initialFeedbackType = $errors->any() || session('error') ? 'error' : (session('success') ? 'success' : '');
                    $initialFeedbackMessage = $errors->any()
                        ? $errors->first()
                        : (session('error') ?: (session('success') ?: ''));
                @endphp

                <div
                    id="loginFeedback"
                    class="login-feedback {{ $initialFeedbackType ? 'is-visible is-' . $initialFeedbackType : '' }}"
                    @if($initialFeedbackType)
                        data-initial-type="{{ $initialFeedbackType }}"
                        data-initial-message="{{ $initialFeedbackMessage }}"
                    @endif
                >
                    <span class="login-feedback-icon" id="loginFeedbackIcon">
                        <i class="fas {{ $initialFeedbackType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
                    </span>
                    <span id="loginFeedbackText">{{ $initialFeedbackMessage }}</span>
                </div>

                <form method="POST" action="{{ route('login') }}" id="loginForm" class="login-form" data-secure-form="1" novalidate>
                    @csrf

                    <div class="login-field">
                        <label for="email" class="login-label">Email professionnel</label>
                        <div class="login-input-wrap">
                            <span class="login-input-icon"><i class="fas fa-envelope"></i></span>
                            <input
                                type="email"
                                class="form-control-modern @error('email') is-invalid @enderror"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="nom@entreprise.com"
                                required
                                autocomplete="email"
                                autofocus
                            >
                        </div>
                        @error('email')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="login-field">
                        <div class="login-label-row">
                            <label for="password" class="login-label">Mot de passe</label>
                            <a href="{{ route('password.request') }}" class="login-inline-link">Mot de passe oublie ?</a>
                        </div>
                        <div class="login-input-wrap">
                            <span class="login-input-icon"><i class="fas fa-lock"></i></span>
                            <input
                                type="password"
                                class="form-control-modern @error('password') is-invalid @enderror"
                                id="password"
                                name="password"
                                placeholder="Entrez votre mot de passe"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Afficher ou masquer le mot de passe">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        @error('password')<span class="form-error">{{ $message }}</span>@enderror
                    </div>

                    <label class="login-checkbox">
                        <input type="checkbox" id="rememberMe" name="remember">
                        <span class="login-checkbox-mark"></span>
                        <span>Se souvenir de moi</span>
                    </label>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-login-label">Se connecter</span>
                        <span class="btn-login-spinner" aria-hidden="true"></span>
                    </button>
                </form>

                <div class="login-divider"><span>ou</span></div>

                <a href="{{ route('auth.google.redirect') }}" class="login-google-btn">
                    <i class="fab fa-google"></i>
                    <span>Continuer avec Google</span>
                </a>

                <div class="login-footer">
                    <span>Pas encore de compte ?</span>
                    <a href="{{ route('register') }}">Creer un compte</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        window.LoginPage = {
            defaultRedirect: @json(url('/dashboard')),
            loginErrorMessage: 'Connexion impossible pour le moment. Reessayez dans un instant.'
        };
    </script>
    @include('layouts.partials.tauri-bridge')
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
