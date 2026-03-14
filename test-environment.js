(function () {
  document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('.test-environment-banner')) {
      return;
    }

    document.body.classList.add('test-environment-active');

    var banner = document.createElement('div');
    banner.className = 'test-environment-banner';
    banner.textContent = '⚠️ Environnement de test : nouvelles fonctionnalités en cours de validation.';

    document.body.insertBefore(banner, document.body.firstChild);
  });
})();
