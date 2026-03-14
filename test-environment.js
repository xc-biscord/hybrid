(function () {
  function addTestEnvironmentMarker() {
    if (!document.body || document.querySelector('.test-environment-banner')) {
      return;
    }

    document.body.classList.add('test-environment-active');

    var banner = document.createElement('div');
    banner.className = 'test-environment-banner';
    banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'polite');
    banner.textContent = '⚠️ Environnement de test : nouvelles fonctionnalités en cours de validation.';

    document.body.appendChild(banner);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addTestEnvironmentMarker);
  } else {
    addTestEnvironmentMarker();
  }
})();
