<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Accepter l'invitation — {{ config('app.name') }}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <style>
    body { background: var(--surface-1); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
    .accept-card { background: var(--surface-0); border: 1px solid var(--c-ink-05); border-radius: var(--r-2xl); box-shadow: var(--shadow-xl); width: 100%; max-width: 480px; overflow: hidden; }
    .accept-header { background: var(--c-ink); padding: 32px; text-align: center; }
    .accept-logo { width: 48px; height: 48px; background: var(--c-accent); border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; margin: 0 auto 16px; }
    .accept-body { padding: 32px; }
    .accept-info { background: var(--c-accent-xl); border: 1px solid var(--c-accent-lt); border-radius: var(--r-md); padding: 16px; margin-bottom: 24px; }
    .accept-info-row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
    .accept-info-label { color: var(--c-ink-40); }
    .accept-info-value { font-weight: var(--fw-medium); color: var(--c-ink); }
    .strength-bar { height: 4px; border-radius: 99px; background: var(--c-ink-05); margin-top: 8px; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 99px; transition: width .3s, background .3s; }
  </style>
</head>
<body>

<div class="accept-card">
  <div class="accept-header">
    <div class="accept-logo"><i class="fas fa-chart-network"></i></div>
    <div style="font-family:var(--ff-display);font-size:18px;font-weight:700;color:#fff;margin-bottom:6px;">
      {{ $invitation->tenant?->name ?? config('app.name') }}
    </div>
    <div style="font-size:13px;color:rgba(255,255,255,.5);">Vous avez été invité à rejoindre cette organisation</div>
  </div>

  <div class="accept-body">
    {{-- Infos invitation --}}
    <div class="accept-info">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--c-ink-40);margin-bottom:10px;">Détails de votre invitation</div>
      <div class="accept-info-row">
        <span class="accept-info-label">Email</span>
        <span class="accept-info-value">{{ $invitation->email }}</span>
      </div>
      <div class="accept-info-row">
        <span class="accept-info-label">Rôle attribué</span>
        <span class="accept-info-value">{{ config("user.tenant_roles.{$invitation->role_in_tenant}", $invitation->role_in_tenant) }}</span>
      </div>
      <div class="accept-info-row">
        <span class="accept-info-label">Invité par</span>
        <span class="accept-info-value">{{ $invitation->invitedBy?->name ?? 'L\'équipe' }}</span>
      </div>
      <div class="accept-info-row">
        <span class="accept-info-label">Expire le</span>
        <span class="accept-info-value" style="{{ $invitation->expires_at->diffInDays(now()) < 2 ? 'color:var(--c-danger);' : '' }}">
          {{ $invitation->expires_at->format('d/m/Y à H:i') }}
        </span>
      </div>
    </div>

    {{-- Formulaire --}}
    <form id="acceptForm" action="{{ route('users.accept.submit', $invitation->token) }}" method="POST">
      @csrf

      <div class="form-group">
        <label class="form-label">Votre nom complet <span class="required">*</span></label>
        <div class="input-group">
          <i class="fas fa-user input-icon"></i>
          <input type="text" name="name" class="form-control" placeholder="Jean Dupont" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Mot de passe <span class="required">*</span></label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="password" class="form-control" placeholder="Min. 8 caractères" required minlength="8" oninput="checkStrength(this.value)">
          <button type="button" onclick="togglePwd('password')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--c-ink-20);cursor:pointer;">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
        <div class="strength-bar"><div class="strength-fill" id="strengthFill" style="width:0;background:var(--c-danger);"></div></div>
        <div style="font-size:11px;color:var(--c-ink-40);margin-top:4px;" id="strengthLabel">Saisissez un mot de passe</div>
      </div>

      <div class="form-group">
        <label class="form-label">Confirmer le mot de passe <span class="required">*</span></label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password_confirmation" class="form-control" placeholder="Répétez votre mot de passe" required>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" id="submitBtn" style="width:100%;justify-content:center;margin-top:8px;">
        <i class="fas fa-check"></i> Créer mon compte et rejoindre l'équipe
      </button>
    </form>

    <div style="text-align:center;margin-top:20px;font-size:12px;color:var(--c-ink-40);">
      Vous avez déjà un compte ? <a href="{{ route('login') }}" style="color:var(--c-accent);">Se connecter</a>
    </div>
  </div>
</div>

<div class="toast-container"></div>

<script src="{{ asset('vendor/client/js/crm.js') }}"></script>
<script>
function togglePwd(id) {
  const input = document.getElementById(id);
  const icon  = document.getElementById('eyeIcon');
  if (input.type === 'password') { input.type = 'text'; icon.className = 'fas fa-eye-slash'; }
  else { input.type = 'password'; icon.className = 'fas fa-eye'; }
}

function checkStrength(val) {
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 8)  score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const configs = [
    { w:'0%',    c:'var(--c-danger)',  t:'Trop court' },
    { w:'25%',   c:'var(--c-danger)',  t:'Faible' },
    { w:'50%',   c:'var(--c-warning)', t:'Moyen' },
    { w:'75%',   c:'var(--c-warning)', t:'Bon' },
    { w:'100%',  c:'var(--c-success)', t:'Excellent' },
  ];
  const cfg = configs[val.length < 8 ? 0 : score];
  fill.style.width      = cfg.w;
  fill.style.background = cfg.c;
  label.textContent     = cfg.t;
}

ajaxForm('acceptForm', {
  onSuccess: (data) => {
    Toast.success('Bienvenue !', data.message, 3000);
    setTimeout(() => window.location.href = data.redirect || '{{ route("login") }}', 1500);
  }
});
</script>
</body>
</html>