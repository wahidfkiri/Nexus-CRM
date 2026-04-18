// ======================== PAGE LOGIN - SCRIPT POUR FORMULAIRE STANDARD ========================

// Loader
const loaderOverlay = document.getElementById('loaderOverlay');

function showLoader() {
    if(loaderOverlay) loaderOverlay.classList.add('active');
}

function hideLoader() {
    if(loaderOverlay) loaderOverlay.classList.remove('active');
}

// Toggle password visibility
const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if(togglePassword && passwordInput) {
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.querySelector('i').classList.toggle('fa-eye');
        togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
    });
}

// Afficher le loader lors de la soumission du formulaire
const loginForm = document.getElementById('loginForm');
const loginBtn = document.getElementById('loginBtn');

if(loginForm) {
    loginForm.addEventListener('submit', (e) => {
        // Afficher le loader
        showLoader();
        
        // Désactiver le bouton pour éviter double soumission
        if(loginBtn) {
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Connexion...';
        }
        
        // Le formulaire sera soumis normalement vers Laravel
        // Pas besoin de preventDefault()
    });
}

// Cacher le loader au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    hideLoader();
    
    // Ajouter des animations aux inputs
    const inputs = document.querySelectorAll('.form-control-modern');
    inputs.forEach((input, index) => {
        input.style.animation = `fadeInUp 0.4s ease ${index * 0.1}s backwards`;
    });
    
    const loginBtnElement = document.querySelector('.btn-login');
    if(loginBtnElement) {
        loginBtnElement.style.animation = 'fadeInUp 0.4s ease 0.3s backwards';
    }
});

// Vérifier si l'utilisateur est déjà connecté (redirection côté serveur déjà gérée)
// Pas besoin de vérification côté client

// Fonction pour les démos (optionnel)
function fillDemoCredentials() {
    const emailInput = document.getElementById('email');
    const passwordField = document.getElementById('password');
    
    if(emailInput && passwordField) {
        emailInput.value = 'admin@nexuscrm.com';
        passwordField.value = 'password123';
        
        // Animation de confirmation
        emailInput.style.transition = 'all 0.2s';
        passwordField.style.transition = 'all 0.2s';
        emailInput.style.borderColor = '#10b981';
        passwordField.style.borderColor = '#10b981';
        
        setTimeout(() => {
            emailInput.style.borderColor = '';
            passwordField.style.borderColor = '';
        }, 1000);
    }
}

window.fillDemoCredentials = fillDemoCredentials;