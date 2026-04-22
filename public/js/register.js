// ======================== PAGE REGISTER - SCRIPT COMPLET AVEC AJAX LARAVEL ========================

// Configuration API
const API_URL = window.Laravel?.apiUrl || '/api';
const CSRF_TOKEN = window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Loader
const loaderOverlay = document.getElementById('loaderOverlay');

function showLoader() {
    if(loaderOverlay) loaderOverlay.classList.add('active');
}

function hideLoader() {
    if(loaderOverlay) loaderOverlay.classList.remove('active');
}

// Toggle password visibility
document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', () => {
        const targetId = button.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if(input) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            button.querySelector('i').classList.toggle('fa-eye');
            button.querySelector('i').classList.toggle('fa-eye-slash');
        }
    });
});

// Password strength checker
const passwordInput = document.getElementById('password');
const strengthProgress = document.getElementById('strengthProgress');
const strengthText = document.getElementById('strengthText');

function checkPasswordStrength(password) {
    let strength = 0;
    let message = '';
    let color = '';
    
    if(password.length === 0) {
        if(strengthProgress) strengthProgress.style.width = '0%';
        if(strengthText) strengthText.textContent = '';
        return;
    }
    
    // Length check
    if(password.length >= 8) strength++;
    if(password.length >= 12) strength++;
    
    // Character variety
    if(/[a-z]/.test(password)) strength++;
    if(/[A-Z]/.test(password)) strength++;
    if(/[0-9]/.test(password)) strength++;
    if(/[^a-zA-Z0-9]/.test(password)) strength++;
    
    // Determine strength
    if(strength <= 2) {
        message = 'Très faible';
        color = '#ef4444';
        if(strengthProgress) strengthProgress.style.width = '20%';
    } else if(strength <= 4) {
        message = 'Faible';
        color = '#f59e0b';
        if(strengthProgress) strengthProgress.style.width = '40%';
    } else if(strength <= 6) {
        message = 'Moyen';
        color = '#eab308';
        if(strengthProgress) strengthProgress.style.width = '60%';
    } else if(strength <= 8) {
        message = 'Fort';
        color = '#10b981';
        if(strengthProgress) strengthProgress.style.width = '80%';
    } else {
        message = 'Très fort';
        color = '#059669';
        if(strengthProgress) strengthProgress.style.width = '100%';
    }
    
    if(strengthProgress) strengthProgress.style.backgroundColor = color;
    if(strengthText) {
        strengthText.textContent = `Force du mot de passe : ${message}`;
        strengthText.style.color = color;
    }
}

if(passwordInput) {
    passwordInput.addEventListener('input', (e) => checkPasswordStrength(e.target.value));
}

// Fonctions pour afficher les messages
function showError(message) {
    let errorDiv = document.querySelector('.error-message');
    if(!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        const form = document.getElementById('registerForm');
        form.insertBefore(errorDiv, form.firstChild);
    }
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    errorDiv.classList.add('show');
    
    const successDiv = document.querySelector('.success-message');
    if(successDiv) successDiv.classList.remove('show');
    
    setTimeout(() => {
        errorDiv.classList.remove('show');
    }, 5000);
}

function showSuccess(message) {
    let successDiv = document.querySelector('.success-message');
    if(!successDiv) {
        successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        const form = document.getElementById('registerForm');
        form.insertBefore(successDiv, form.firstChild);
    }
    successDiv.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    successDiv.classList.add('show');
    
    const errorDiv = document.querySelector('.error-message');
    if(errorDiv) errorDiv.classList.remove('show');
    
    setTimeout(() => {
        successDiv.classList.remove('show');
    }, 4000);
}

// Retirer les styles d'erreur
function removeErrors() {
    document.querySelectorAll('.form-control-modern').forEach(input => {
        input.classList.remove('error');
    });
}

// Validation email
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function sanitizeClientText(value) {
    return String(value || '')
        .replace(/<[^>]*>/g, '')
        .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/g, '')
        .trim();
}

// Validation mot de passe
function isValidPassword(password) {
    return password.length >= 8;
}

// Modal handling
const termsModal = document.getElementById('termsModal');
const privacyModal = document.getElementById('privacyModal');
const termsLink = document.getElementById('termsLink');
const privacyLink = document.getElementById('privacyLink');
const closeTermsModal = document.getElementById('closeTermsModal');
const closePrivacyModal = document.getElementById('closePrivacyModal');
const acceptTermsBtn = document.getElementById('acceptTermsBtn');
const acceptPrivacyBtn = document.getElementById('acceptPrivacyBtn');
const termsCheckbox = document.getElementById('termsCheckbox');

let termsAccepted = false;
let privacyAccepted = false;

function openModal(modal) {
    if(modal) modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    if(modal) modal.classList.remove('active');
    document.body.style.overflow = '';
}

if(termsLink) {
    termsLink.addEventListener('click', (e) => {
        e.preventDefault();
        openModal(termsModal);
    });
}

if(privacyLink) {
    privacyLink.addEventListener('click', (e) => {
        e.preventDefault();
        openModal(privacyModal);
    });
}

if(closeTermsModal) {
    closeTermsModal.addEventListener('click', () => closeModal(termsModal));
}

if(closePrivacyModal) {
    closePrivacyModal.addEventListener('click', () => closeModal(privacyModal));
}

// Fermer les modals en cliquant à l'extérieur
if(termsModal) {
    termsModal.addEventListener('click', (e) => {
        if(e.target === termsModal) closeModal(termsModal);
    });
}

if(privacyModal) {
    privacyModal.addEventListener('click', (e) => {
        if(e.target === privacyModal) closeModal(privacyModal);
    });
}

// Accepter les conditions
if(acceptTermsBtn) {
    acceptTermsBtn.addEventListener('click', () => {
        termsAccepted = true;
        closeModal(termsModal);
        checkTermsAndPrivacy();
    });
}

// Accepter la politique de confidentialité
if(acceptPrivacyBtn) {
    acceptPrivacyBtn.addEventListener('click', () => {
        privacyAccepted = true;
        closeModal(privacyModal);
        checkTermsAndPrivacy();
    });
}

// Vérifier et cocher la checkbox si les deux sont acceptés
function checkTermsAndPrivacy() {
    if(termsAccepted && privacyAccepted && termsCheckbox) {
        termsCheckbox.checked = true;
        showSuccess('Conditions et politique de confidentialité acceptées');
    }
}

// Réinitialiser les acceptations si la checkbox est décochée manuellement
if(termsCheckbox) {
    termsCheckbox.addEventListener('change', (e) => {
        if(!e.target.checked) {
            termsAccepted = false;
            privacyAccepted = false;
        }
    });
}

// ======================== SOCIAL REGISTER ========================

// Fonction générique pour les inscriptions sociales avec Laravel
async function socialRegister(provider) {
    const socialBtn = document.getElementById(`${provider}Register`);
    const originalContent = socialBtn.innerHTML;
    
    // Afficher le loader sur le bouton
    socialBtn.classList.add('loading');
    socialBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> <span>Connexion...</span>';
    socialBtn.disabled = true;
    
    try {
        // Appel API pour obtenir l'URL de redirection OAuth
        const response = await fetch(`${API_URL}/auth/${provider}/redirect`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });
        
        const data = await response.json();
        
        if(response.ok && data.redirect_url) {
            // Rediriger vers l'URL OAuth du fournisseur
            window.location.href = data.redirect_url;
        } else {
            throw new Error('Impossible de démarrer l\'authentification');
        }
    } catch (error) {
        console.error(`Erreur d'inscription ${provider}:`, error);
        socialBtn.classList.remove('loading');
        socialBtn.innerHTML = originalContent;
        socialBtn.disabled = false;
        showError(`Échec de l'inscription avec ${provider === 'google' ? 'Google' : 'Facebook'}. Veuillez réessayer.`);
    }
}

// Événements pour les boutons sociaux
const googleRegisterBtn = document.getElementById('googleRegister');
const facebookRegisterBtn = document.getElementById('facebookRegister');

if(googleRegisterBtn) {
    googleRegisterBtn.addEventListener('click', () => socialRegister('google'));
}

if(facebookRegisterBtn) {
    facebookRegisterBtn.addEventListener('click', () => socialRegister('facebook'));
}

// ======================== FORMULAIRE D'INSCRIPTION AVEC AJAX ========================

const registerForm = document.getElementById('registerForm');
const registerBtn = document.getElementById('registerBtn');
let isSubmitting = false;

// Validation en temps réel du mot de passe et confirmation
const confirmPasswordInput = document.getElementById('confirmPassword');

function validatePasswordMatch() {
    if(confirmPasswordInput && passwordInput) {
        const password = passwordInput.value;
        const confirm = confirmPasswordInput.value;
        
        if(confirm.length > 0 && password !== confirm) {
            confirmPasswordInput.classList.add('error');
            return false;
        } else {
            confirmPasswordInput.classList.remove('error');
            return true;
        }
    }
    return true;
}

if(confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
}

if(passwordInput) {
    passwordInput.addEventListener('input', validatePasswordMatch);
}

// Soumission du formulaire avec AJAX
if(registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if(isSubmitting) return;
        
        // Récupérer les valeurs
        const firstName = sanitizeClientText(document.getElementById('firstName').value);
        const lastName = sanitizeClientText(document.getElementById('lastName').value);
        const email = sanitizeClientText(document.getElementById('email').value);
        const company = sanitizeClientText(document.getElementById('company').value);
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Retirer les erreurs précédentes
        removeErrors();
        
        // Validation des champs
        let hasError = false;
        
        if(!firstName) {
            document.getElementById('firstName').classList.add('error');
            showError('Veuillez entrer votre prénom');
            hasError = true;
        } else if(firstName.length < 2) {
            document.getElementById('firstName').classList.add('error');
            showError('Le prénom doit contenir au moins 2 caractères');
            hasError = true;
        }
        
        if(!lastName) {
            document.getElementById('lastName').classList.add('error');
            if(!hasError) showError('Veuillez entrer votre nom');
            hasError = true;
        } else if(lastName.length < 2) {
            document.getElementById('lastName').classList.add('error');
            showError('Le nom doit contenir au moins 2 caractères');
            hasError = true;
        }
        
        if(!email) {
            document.getElementById('email').classList.add('error');
            if(!hasError) showError('Veuillez entrer votre email');
            hasError = true;
        } else if(!isValidEmail(email)) {
            document.getElementById('email').classList.add('error');
            showError('Veuillez entrer un email valide');
            hasError = true;
        }
        
        if(!password) {
            document.getElementById('password').classList.add('error');
            if(!hasError) showError('Veuillez choisir un mot de passe');
            hasError = true;
        } else if(!isValidPassword(password)) {
            document.getElementById('password').classList.add('error');
            showError('Le mot de passe doit contenir au moins 8 caractères');
            hasError = true;
        } else if(!/[A-Z]/.test(password)) {
            document.getElementById('password').classList.add('error');
            showError('Le mot de passe doit contenir au moins une majuscule');
            hasError = true;
        } else if(!/[a-z]/.test(password)) {
            document.getElementById('password').classList.add('error');
            showError('Le mot de passe doit contenir au moins une minuscule');
            hasError = true;
        } else if(!/[0-9]/.test(password)) {
            document.getElementById('password').classList.add('error');
            showError('Le mot de passe doit contenir au moins un chiffre');
            hasError = true;
        } else if(!/[^A-Za-z0-9]/.test(password)) {
            document.getElementById('password').classList.add('error');
            showError('Le mot de passe doit contenir au moins un caractère spécial');
            hasError = true;
        }
        
        if(!confirmPassword) {
            document.getElementById('confirmPassword').classList.add('error');
            if(!hasError) showError('Veuillez confirmer votre mot de passe');
            hasError = true;
        } else if(password !== confirmPassword) {
            document.getElementById('confirmPassword').classList.add('error');
            showError('Les mots de passe ne correspondent pas');
            hasError = true;
        }
        
        if(!termsCheckbox || !termsCheckbox.checked) {
            showError('Vous devez accepter les conditions d\'utilisation');
            hasError = true;
        }
        
        if(hasError) return;
        
        // Afficher le loader et désactiver le bouton
        showLoader();
        isSubmitting = true;
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Inscription...';
        const requestId = window.SecureForm?.ensureRequestId
            ? window.SecureForm.ensureRequestId(registerForm)
            : `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
        
        try {
            // Appel API Laravel
            const response = await fetch(`${API_URL}/register`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Request-Id': requestId,
                    'Idempotency-Key': requestId
                },
                body: JSON.stringify({
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    company: company || null,
                    password: password,
                    password_confirmation: confirmPassword,
                    _request_id: requestId
                })
            });
            
            const data = await response.json();
            
            if(response.ok && data.success) {
                // Stocker les informations utilisateur
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userEmail', data.user.email);
                localStorage.setItem('userName', data.user.name);
                localStorage.setItem('userFirstName', firstName);
                localStorage.setItem('userLastName', lastName);
                localStorage.setItem('userAvatar', data.user.avatar || `${firstName.charAt(0)}${lastName.charAt(0)}`);
                localStorage.setItem('userRole', data.user.role || 'user');
                localStorage.setItem('accessToken', data.token);
                
                // Animation de succès
                registerBtn.innerHTML = '<i class="fas fa-check"></i> Inscription réussie...';
                registerBtn.style.background = '#10b981';
                
                // Redirection vers le dashboard
                setTimeout(() => {
                    window.location.href = data.redirect || '/dashboard';
                }, 1000);
            } else {
                hideLoader();
                isSubmitting = false;
                registerBtn.disabled = false;
                registerBtn.innerHTML = '<span>S\'inscrire</span> <i class="fas fa-arrow-right"></i>';
                registerBtn.style.background = '';
                
                // Afficher les erreurs de validation
                if(data.errors) {
                    const firstError = Object.values(data.errors)[0];
                    showError(firstError?.[0] || 'Erreur de validation');
                } else {
                    showError(data.message || 'Une erreur est survenue. Veuillez réessayer.');
                }
                
                // Mettre en évidence les champs en erreur
                if(data.errors) {
                    const fieldMap = {
                        first_name: 'firstName',
                        last_name: 'lastName',
                        password_confirmation: 'confirmPassword',
                    };
                    Object.keys(data.errors).forEach(field => {
                        const fieldElement = document.getElementById(fieldMap[field] || field);
                        if(fieldElement) fieldElement.classList.add('error');
                    });
                }
                
                // Secouer le formulaire
                const wrapper = document.querySelector('.form-wrapper');
                wrapper.style.animation = 'shake 0.3s ease';
                setTimeout(() => {
                    wrapper.style.animation = '';
                }, 300);
            }
        } catch (error) {
            console.error('Erreur d\'inscription:', error);
            hideLoader();
            isSubmitting = false;
            registerBtn.disabled = false;
            registerBtn.innerHTML = '<span>S\'inscrire</span> <i class="fas fa-arrow-right"></i>';
            registerBtn.style.background = '';
            showError('Erreur de connexion au serveur. Veuillez réessayer.');
        }
    });
}

// ======================== ANIMATIONS ET EFFETS ========================

// Animation des champs au focus
document.querySelectorAll('.form-control-modern').forEach(input => {
    input.addEventListener('focus', () => {
        input.parentElement.classList.add('focused');
    });
    
    input.addEventListener('blur', () => {
        input.parentElement.classList.remove('focused');
    });
});

// Validation en temps réel de l'email
const emailField = document.getElementById('email');
if(emailField) {
    emailField.addEventListener('blur', () => {
        const email = emailField.value.trim();
        if(email && !isValidEmail(email)) {
            emailField.classList.add('error');
        } else {
            emailField.classList.remove('error');
        }
    });
}

// Validation en temps réel du prénom et nom
const firstNameField = document.getElementById('firstName');
const lastNameField = document.getElementById('lastName');

function validateNameField(field, minLength = 2) {
    const value = field.value.trim();
    if(value && value.length < minLength) {
        field.classList.add('error');
        return false;
    } else {
        field.classList.remove('error');
        return true;
    }
}

if(firstNameField) {
    firstNameField.addEventListener('blur', () => validateNameField(firstNameField));
}

if(lastNameField) {
    lastNameField.addEventListener('blur', () => validateNameField(lastNameField));
}

// ======================== VÉRIFICATION DE SESSION ========================

// Vérifier si l'utilisateur est déjà connecté
function checkAuth() {
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true' || sessionStorage.getItem('isLoggedIn') === 'true';
    if(isLoggedIn && window.location.pathname.includes('register.html')) {
        window.location.href = '/dashboard';
    }
}

// Exécuter la vérification
checkAuth();

// ======================== STYLES ADDITIONNELS DYNAMIQUES ========================

// Ajouter des styles pour les animations
const style = document.createElement('style');
style.textContent = `
    .focused .form-control-modern {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .shake {
        animation: shake 0.3s ease;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .form-control-modern.error {
        border-color: #ef4444;
        animation: shake 0.3s ease;
    }
    
    .btn-register:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .social-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
`;
document.head.appendChild(style);

// ======================== INITIALISATION ========================

// Animation d'entrée des champs
document.addEventListener('DOMContentLoaded', () => {
    // Ajouter des animations aux inputs
    const inputs = document.querySelectorAll('.form-control-modern');
    inputs.forEach((input, index) => {
        input.style.opacity = '0';
        input.style.transform = 'translateY(10px)';
        input.style.transition = `all 0.3s ease ${index * 0.05}s`;
        
        setTimeout(() => {
            input.style.opacity = '1';
            input.style.transform = 'translateY(0)';
        }, 100);
    });
    
    // Animation du bouton d'inscription
    if(registerBtn) {
        registerBtn.style.opacity = '0';
        registerBtn.style.transform = 'translateY(10px)';
        setTimeout(() => {
            registerBtn.style.opacity = '1';
            registerBtn.style.transform = 'translateY(0)';
        }, 300);
    }
    
    // Vérifier si les conditions étaient déjà acceptées dans la session
    const savedTermsAccepted = sessionStorage.getItem('termsAccepted');
    const savedPrivacyAccepted = sessionStorage.getItem('privacyAccepted');
    
    if(savedTermsAccepted === 'true') termsAccepted = true;
    if(savedPrivacyAccepted === 'true') privacyAccepted = true;
    
    if(termsAccepted && privacyAccepted && termsCheckbox) {
        termsCheckbox.checked = true;
    }
});

// Sauvegarder l'acceptation des conditions dans la session
if(acceptTermsBtn) {
    acceptTermsBtn.addEventListener('click', () => {
        sessionStorage.setItem('termsAccepted', 'true');
    });
}

if(acceptPrivacyBtn) {
    acceptPrivacyBtn.addEventListener('click', () => {
        sessionStorage.setItem('privacyAccepted', 'true');
    });
}

console.log('Register.js chargé avec succès');
