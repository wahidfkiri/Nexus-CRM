<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Inscription | NexusCRM Admin</title>
    <!-- Bootstrap 5 CSS + Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css">
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

    <div class="register-container">
        <div class="register-wrapper">
            <!-- Left side - Branding -->
            <div class="register-branding">
                <div class="brand-content">
                    <div class="brand-logo">
                        <i class="fas fa-chart-network"></i>
                        <span>NexusCRM</span>
                    </div>
                    <h1>Créez votre compte</h1>
                    <p>Rejoignez NexusCRM et découvrez une nouvelle façon de piloter vos performances.</p>
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
                        <div class="feature">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Stockage illimité</span>
                        </div>
                    </div>
                </div>
                <div class="brand-footer">
                    <p>© 2025 NexusCRM - Tous droits réservés</p>
                </div>
            </div>

            <!-- Right side - Register Form -->
            <div class="register-form-container">
                <div class="form-wrapper">
                    <div class="form-header">
                        <h2>Inscription</h2>
                        <p>Créez votre compte gratuitement</p>
                    </div>

                    <form id="registerForm">
                        <div class="form-row">
                            <div class="input-group-custom">
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="text" class="form-control-modern" id="firstName" name="first_name" placeholder="Prénom" autocomplete="given-name">
                            </div>

                            <div class="input-group-custom">
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="text" class="form-control-modern" id="lastName" name="last_name" placeholder="Nom" autocomplete="family-name">
                            </div>
                        </div>

                        <div class="input-group-custom">
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <input type="email" class="form-control-modern" id="email" name="email" placeholder="Email professionnel" autocomplete="email">
                        </div>

                        <div class="input-group-custom">
                            <div class="input-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <input type="text" class="form-control-modern" id="company" name="company" placeholder="Nom de l'entreprise (optionnel)" autocomplete="organization">
                        </div>

                        <div class="form-row">
                            <div class="input-group-custom">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input type="password" class="form-control-modern" id="password" name="password" placeholder="Mot de passe" autocomplete="new-password">
                                <button type="button" class="toggle-password" data-target="password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>

                            <div class="input-group-custom">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input type="password" class="form-control-modern" id="confirmPassword" name="password_confirmation" placeholder="Confirmer mot de passe" autocomplete="off">
                                <button type="button" class="toggle-password" data-target="confirmPassword">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Password strength indicator -->
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar">
                                <div class="strength-progress" id="strengthProgress"></div>
                            </div>
                            <div class="strength-text" id="strengthText"></div>
                        </div>

                        <div class="form-options">
                            <label class="checkbox-custom">
                                <input type="checkbox" id="termsCheckbox" required>
                                <span class="checkmark"></span>
                                J'accepte les <a href="#" id="termsLink">conditions d'utilisation</a> et la <a href="#" id="privacyLink">politique de confidentialité</a>
                            </label>
                        </div>

                        <button type="submit" class="btn-register" id="registerBtn">
                            <span>S'inscrire</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- Social Login Section -->
                        <div class="social-register">
                            <div class="divider">
                                <span>Ou s'inscrire avec</span>
                            </div>
                            
                            <div class="social-buttons">
                                <button type="button" class="social-btn google-btn" id="googleRegister">
                                    <i class="fab fa-google"></i>
                                    <span>Google</span>
                                </button>
                                <button type="button" class="social-btn facebook-btn" id="facebookRegister">
                                    <i class="fab fa-facebook-f"></i>
                                    <span>Facebook</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="form-footer">
                        <p>Déjà un compte ? <a href="{{ route('login') }}">Se connecter</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal-overlay" id="termsModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Conditions d'utilisation</h3>
                <button class="modal-close" id="closeTermsModal">&times;</button>
            </div>
            <div class="modal-body">
                <h4>1. Acceptation des conditions</h4>
                <p>En accédant et en utilisant NexusCRM, vous acceptez d'être lié par ces conditions d'utilisation.</p>
                <h4>2. Utilisation du service</h4>
                <p>Vous acceptez d'utiliser NexusCRM uniquement à des fins légales et conformément à ces conditions.</p>
                <h4>3. Compte utilisateur</h4>
                <p>Vous êtes responsable de la confidentialité de votre compte et de tous les mots de passe.</p>
                <h4>4. Protection des données</h4>
                <p>Nous nous engageons à protéger vos données personnelles conformément au RGPD.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-modal" id="acceptTermsBtn">J'accepte</button>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal-overlay" id="privacyModal">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Politique de confidentialité</h3>
                <button class="modal-close" id="closePrivacyModal">&times;</button>
            </div>
            <div class="modal-body">
                <h4>Collecte des données</h4>
                <p>Nous collectons les informations que vous fournissez lors de l'inscription : nom, email, entreprise.</p>
                <h4>Utilisation des données</h4>
                <p>Vos données sont utilisées pour fournir et améliorer nos services, et pour communiquer avec vous.</p>
                <h4>Partage des données</h4>
                <p>Nous ne partageons pas vos données personnelles avec des tiers sans votre consentement.</p>
                <h4>Vos droits</h4>
                <p>Conformément au RGPD, vous avez le droit d'accéder, modifier ou supprimer vos données.</p>
            </div>
            <div class="modal-footer">
                <button class="btn-modal" id="acceptPrivacyBtn">J'accepte</button>
            </div>
        </div>
    </div>

    <!-- Configuration globale -->
    <script>
        window.Laravel = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            apiUrl: '{{ url("/api") }}'
        };
    </script>
    <script src="{{ asset('vendor/client/js/secure-form.js') }}"></script>
    <script src="js/register.js"></script>
</body>
</html>
