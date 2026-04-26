<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Accepter l'invitation - {{ config('app.name') }}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <style>
    html, body { height: 100%; }
    body { margin: 0; background: var(--surface-1); }
    .accept-page { min-height: 100vh; display: grid; place-items: center; padding: 24px; box-sizing: border-box; }
    .accept-card { background: var(--surface-0); border: 1px solid var(--c-ink-05); border-radius: var(--r-2xl); box-shadow: var(--shadow-xl); width: 100%; max-width: 480px; overflow: hidden; }
    .accept-header { background: var(--c-ink); padding: 32px; text-align: center; }
    .accept-logo { width: 56px; height: 56px; border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; overflow: hidden; background: #fff; border: 1px solid rgba(255,255,255,.18); }
    .accept-logo img { width: 100%; height: 100%; object-fit: contain; display: block; }
    .accept-body { padding: 32px; }
    .accept-info { background: var(--c-accent-xl); border: 1px solid var(--c-accent-lt); border-radius: var(--r-md); padding: 16px; margin-bottom: 24px; }
    .accept-info-row { display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 4px 0; }
    .accept-info-label { color: var(--c-ink-40); }
    .accept-info-value { font-weight: var(--fw-medium); color: var(--c-ink); text-align: right; }
  </style>
</head>
<body>
<div class="accept-page">
  <div class="accept-card">
    <div class="accept-header">
      <div class="accept-logo">
        <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}">
      </div>
      <div style="font-family:var(--ff-display);font-size:18px;font-weight:700;color:#fff;margin-bottom:6px;">
        {{ $invitation->tenant?->name ?? config('app.name') }}
      </div>
      <div style="font-size:13px;color:rgba(255,255,255,.55);">Votre compte va être ajouté à cet espace.</div>
    </div>

    <div class="accept-body">
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
          <span class="accept-info-value">{{ $invitation->invitedBy?->name ?? "L'équipe" }}</span>
        </div>
        <div class="accept-info-row">
          <span class="accept-info-label">Expire le</span>
          <span class="accept-info-value" style="{{ $invitation->expires_at->diffInDays(now()) < 2 ? 'color:var(--c-danger);' : '' }}">
            {{ $invitation->expires_at->format('d/m/Y à H:i') }}
          </span>
        </div>
      </div>

      <div class="form-group">
        <div style="background:var(--c-accent-xl);border:1px solid var(--c-accent-lt);border-radius:var(--r-md);padding:10px 12px;font-size:12px;color:var(--c-ink-60);">
          Vous êtes connecté avec <strong>{{ auth()->user()->email }}</strong>. L’acceptation ajoutera uniquement cet espace à votre compte existant.
        </div>
      </div>

      <form id="acceptForm" action="{{ route('users.accept.submit', $invitation->token) }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary" id="submitBtn" style="width:100%;justify-content:center;margin-top:8px;">
          <i class="fas fa-check"></i> Rejoindre cette équipe
        </button>
      </form>

      <div style="text-align:center;margin-top:20px;font-size:12px;color:var(--c-ink-40);">
        Besoin d’un autre compte ? <a href="{{ route('login') }}" style="color:var(--c-accent);">Changer de connexion</a>
      </div>
    </div>
  </div>
</div>

<div class="toast-container"></div>

<script src="{{ asset('vendor/client/js/crm.js') }}"></script>
<script>
ajaxForm('acceptForm', {
  onSuccess: (data) => {
    Toast.success('Bienvenue', data.message, 3000);
    setTimeout(() => window.location.href = data.redirect || '{{ url("/dashboard") }}', 1200);
  }
});
</script>
</body>
</html>
