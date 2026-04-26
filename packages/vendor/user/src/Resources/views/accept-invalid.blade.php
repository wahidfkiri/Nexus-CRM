<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invitation invalide — {{ config('app.name') }}</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('vendor/client/css/crm.css') }}">
  <style>
    body { background: var(--surface-1); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 24px; }
    .invalid-card { background: var(--surface-0); border: 1px solid var(--c-ink-05); border-radius: var(--r-2xl); box-shadow: var(--shadow-xl); width: 100%; max-width: 520px; overflow: hidden; }
    .invalid-header { background: var(--c-ink); padding: 28px; text-align: center; }
    .invalid-icon { width: 52px; height: 52px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; background: #fee2e2; color: #dc2626; font-size: 24px; margin-bottom: 12px; }
    .invalid-body { padding: 28px; }
    .invalid-reason { margin-top: 10px; padding: 12px 14px; border-radius: 10px; border: 1px solid #fecaca; background: #fff1f2; color: #9f1239; font-size: 14px; }
  </style>
</head>
<body>
  <div class="invalid-card">
    <div class="invalid-header">
      <div class="invalid-icon"><i class="fas fa-link-slash"></i></div>
      <div style="font-family:var(--ff-display);font-size:20px;font-weight:700;color:#fff;">Lien d'invitation invalide</div>
      <div style="font-size:13px;color:rgba(255,255,255,.55);margin-top:6px;">Le lien n'est plus utilisable.</div>
    </div>
    <div class="invalid-body">
      <p style="margin:0;color:var(--c-ink-70);font-size:14px;line-height:1.65;">
        Cette invitation peut être expirée, révoquée, déjà acceptée, ou incorrecte.
      </p>
      @if(!empty($reason))
        <div class="invalid-reason">{{ $reason }}</div>
      @endif
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px;">
        <a href="{{ route('login') }}" class="btn btn-primary">
          <i class="fas fa-right-to-bracket"></i> Se connecter
        </a>
        <a href="{{ url('/') }}" class="btn btn-secondary">
          <i class="fas fa-house"></i> Accueil
        </a>
      </div>
    </div>
  </div>
</body>
</html>

