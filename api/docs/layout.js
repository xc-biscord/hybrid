async function injectLayout() {
    try {
        const cacheBuster = '?v=' + Date.now();

        const [header, sidebar, footer] = await Promise.all([
          fetch('components/header.html' + cacheBuster).then(res => res.text()),
          fetch('components/sidebar.html' + cacheBuster).then(res => res.text()),
          fetch('components/footer.html' + cacheBuster).then(res => res.text())
        ]);
        
  
      // Garde une copie du body d'origine (le contenu propre à la page)
      const pageContent = document.body.innerHTML;
  
      // Construit le layout global
      document.body.innerHTML = `
        ${header}
        <div id="container">
          ${sidebar}
          <main id="main-content">
            ${pageContent}
          </main>
        </div>
        ${footer}
      `;
    } catch (error) {
      console.error("Erreur lors du chargement du layout :", error);
      document.body.innerHTML = `
        <div style="padding:2rem; color:red;">
          <h1>⚠️ Erreur de chargement du layout</h1>
          <p>${error.message}</p>
        </div>
      `;
    }
  }
  
  window.addEventListener('DOMContentLoaded', injectLayout);
  