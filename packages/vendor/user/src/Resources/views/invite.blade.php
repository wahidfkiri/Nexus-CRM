@extends('layouts.global')

@section('title', __('user::users.titles.invite_member'))

@section('breadcrumb')
  <a href="{{ route('users.index') }}">{{ __('user::users.breadcrumbs.team') }}</a>
  <i class="fas fa-chevron-right" style="font-size:10px;color:var(--c-ink-20)"></i>
  <span style="color:var(--c-ink)">{{ __('user::users.breadcrumbs.invite') }}</span>
@endsection

@section('content')
@php
  $inviteI18n = [
      'success' => __('user::users.messages.invitation_sent_toast'),
  ];
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ __('user::users.titles.invite_member') }}</h1>
    <p>{{ __('user::users.subtitles.invite_member') }}</p>
  </div>
  <a href="{{ route('users.index') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left"></i> {{ __('user::users.actions.back') }}
  </a>
</div>

<div class="row" style="max-width:820px;">
  <div class="col-12">
    <form id="inviteForm" action="{{ route('users.store') }}" method="POST">
      @csrf

      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-envelope"></i> {{ __('user::users.headings.invitation_information') }}
          <span class="form-section-badge">{{ __('user::users.badges.step_1_of_2') }}</span>
        </h3>

        <div class="form-group">
          <label class="form-label">{{ __('user::users.fields.email_address') }} <span class="required">*</span></label>
          <div class="input-group">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" placeholder="{{ __('user::users.placeholders.collaborator_email') }}" autofocus required>
          </div>
          <span class="form-hint">{{ __('user::users.subtitles.invitation_email_hint') }}</span>
        </div>

        <div class="form-group">
          <label class="form-label">{{ __('user::users.fields.role') }} <span class="required">*</span></label>
          <div class="row" style="margin-top:8px;">
            @foreach($roles as $key => $label)
            <div class="col-6" style="margin-bottom:10px;">
              <label style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px;border:1.5px solid var(--c-ink-10);border-radius:var(--r-md);cursor:pointer;transition:all var(--dur-fast);"
                     class="role-card" data-role="{{ $key }}">
                <input type="radio" name="role_in_tenant" value="{{ $key }}" style="margin-top:2px;"
                  {{ $key === 'user' ? 'checked' : '' }}>
                <div>
                  <div style="font-weight:var(--fw-medium);color:var(--c-ink);margin-bottom:3px;">{{ $label }}</div>
                  <div style="font-size:12px;color:var(--c-ink-40);">
                    {{ __('user::users.role_descriptions.' . $key, [], app()->getLocale()) !== 'user::users.role_descriptions.' . $key ? __('user::users.role_descriptions.' . $key) : __('user::users.role_descriptions.default') }}
                  </div>
                </div>
              </label>
            </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="form-section">
        <h3 class="form-section-title">
          <i class="fas fa-message"></i> {{ __('user::users.headings.custom_message') }}
          <span class="form-section-badge">{{ __('user::users.badges.step_2_of_2_optional') }}</span>
        </h3>
        <div class="form-group">
          <label class="form-label">{{ __('user::users.fields.message') }} <span class="hint">({{ __('user::users.fields.optional') }})</span></label>
          <textarea name="message" class="form-control" rows="3"
            placeholder="{{ __('user::users.placeholders.invitation_message') }}"></textarea>
          <span class="form-hint">{{ __('user::users.subtitles.message_hint') }}</span>
        </div>

        <div style="background:var(--c-accent-xl);border:1px solid var(--c-accent-lt);border-radius:var(--r-md);padding:16px 20px;font-size:13px;color:var(--c-ink-60);">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;font-weight:var(--fw-semi);color:var(--c-ink);">
            <i class="fas fa-circle-info" style="color:var(--c-accent);"></i>
            {{ __('user::users.headings.what_guest_receives') }}
          </div>
          <ul style="margin:0;padding-left:18px;line-height:1.9;">
            <li>{{ __('user::users.hints.email_expiry', ['days' => config('user.invitation.expire_days', 7)]) }}</li>
            <li>{{ __('user::users.hints.password_form') }}</li>
            <li>{{ __('user::users.hints.immediate_access') }}</li>
          </ul>
        </div>
      </div>

      <div class="form-actions" style="padding-top:8px;">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">
          <i class="fas fa-times"></i> {{ __('user::users.actions.cancel') }}
        </a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
          <i class="fas fa-paper-plane"></i> {{ __('user::users.actions.send_invitation') }}
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
window.INVITE_I18N = @json($inviteI18n);
document.querySelectorAll('.role-card').forEach(card => {
  const radio = card.querySelector('input[type=radio]');
  function highlight() {
    document.querySelectorAll('.role-card').forEach(c => {
      c.style.borderColor = 'var(--c-ink-10)';
      c.style.background = '';
    });
    if (radio.checked) {
      card.style.borderColor = 'var(--c-accent)';
      card.style.background = 'var(--c-accent-xl)';
    }
  }
  radio.addEventListener('change', () => {
    document.querySelectorAll('input[name=role_in_tenant]').forEach(r => {
      r.closest('.role-card').style.borderColor = 'var(--c-ink-10)';
    });
    highlight();
  });
  if (radio.checked) highlight();
});

ajaxForm('inviteForm', {
  onSuccess: (data) => {
    Toast.success(window.INVITE_I18N.success, data.message, 4000);
  }
});
</script>
@endpush