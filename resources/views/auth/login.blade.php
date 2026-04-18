<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Connexion | NexusCRM Admin</title>
    <!-- Bootstrap 5 CSS + Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <!-- CSRF Token pour Laravel -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

    <!-- Loader -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader">
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-ring"></div>
            <div class="loader-logo">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <div class="login-container">
        <div class="login-wrapper">
            <!-- Left side - Branding -->
            <div class="login-branding">
                <div class="brand-content">
                    <div class="brand-logo">
                        <i class="fas fa-chart-network"></i>
                        <span>NexusCRM</span>
                    </div>
                    <h1>Bienvenue</h1>
                    <p>Connectez-vous pour accéder à votre espace d'administration et piloter vos performances en temps réel.</p>
                    <div class="brand-features">
                        <div class="feature">
                            <i class="fas fa-chart-line"></i>
                            <span>Analytiques avancées</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-shield-alt"></i>
                            <span>Sécurité renforcée</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-headset"></i>
                            <span>Support 24/7</span>
                        </div>
                    </div>
                </div>
                <div class="brand-footer">
                    <p>© 2025 NexusCRM - Tous droits réservés</p>
                </div>
            </div>

            <!-- Right side - Login Form -->
            <div class="login-form-container">
                <div class="form-wrapper">
                    <div class="form-header">
                        <h2>Connexion</h2>
                        <p>Entrez vos identifiants pour continuer</p>
                    </div>

                    <!-- Affichage des erreurs de session -->
                    @if($errors->any())
                        <div class="error-message show">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="success-message show">
                            <i class="fas fa-check-circle"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="error-message show">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        @csrf
                        <div class="input-group-custom">
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <input type="email" class="form-control-modern @error('email') error @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="Email professionnel" required autocomplete="email" autofocus>
                        </div>

                        <div class="input-group-custom">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <input type="password" class="form-control-modern @error('password') error @enderror" id="password" name="password" placeholder="Mot de passe" required autocomplete="current-password">
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-custom">
                                <input type="checkbox" id="rememberMe" name="remember">
                                <span class="checkmark"></span>
                                Se souvenir de moi
                            </label>
                            <a href="{{ route('password.request') }}" class="forgot-link">Mot de passe oublié ?</a>
                        </div>

                        <button type="submit" class="btn-login" id="loginBtn">
                            <span>Se connecter</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Social Login Section -->
                        <div class="social-login">
                            <div class="divider">
                                <span>Ou continuer avec</span>
                            </div>
                            
                            <div class="social-buttons">
                                <a href="{{ url('/auth/google/redirect') }}" class="social-btn google-btn">
                                    <i class="fab fa-google"></i>
                                    <span>Google</span>
                                </a>
                                <a href="{{ url('/auth/facebook/redirect') }}" class="social-btn facebook-btn">
                                    <i class="fab fa-facebook-f"></i>
                                    <span>Facebook</span>
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="form-footer">
                        <p>Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration globale -->
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            apiUrl: '{{ url("/api") }}'
        };
    </script>
    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>