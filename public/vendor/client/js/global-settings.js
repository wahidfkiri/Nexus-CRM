if (!window.__CRM_GLOBAL_SETTINGS_JS__) {
  window.__CRM_GLOBAL_SETTINGS_JS__ = true;

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('globalSettingsForm');
    if (!form || form.dataset.secureAjax !== '1') {
      return;
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!window.SecureForm) {
        form.submit();
        return;
      }

      const submitButton = document.getElementById('globalSettingsSaveBtn') || form.querySelector('button[type="submit"]');
      const result = await window.SecureForm.submit(form, {
        method: 'POST',
        submitButton,
      });

      if (result.ok) {
        if (window.Toast) {
          window.Toast.success('Succès', result.data?.message || 'Paramètres enregistrés.');
        }
        return;
      }

      if (result.status === 422) {
        if (window.Toast) {
          window.Toast.error('Validation', result.data?.message || 'Veuillez corriger les champs.');
        }
        return;
      }

      if (window.Toast) {
        window.Toast.error('Erreur', result.data?.message || 'Impossible de sauvegarder les paramètres.');
      }
    });
  });
}
