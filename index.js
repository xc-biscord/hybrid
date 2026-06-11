document.addEventListener("DOMContentLoaded", () => {
  const API_BASE = "/api";

  const formInscription = document.getElementById("form-inscription");
  const formConnexion = document.getElementById("form-connexion");
  const connectedBlock = document.getElementById("connected-user-message");
  const connectedUsername = document.getElementById("connected-username");
  const passwordInput = document.getElementById("mdp");
  const strengthBar = document.querySelector(".strength-bar");

  // ------------------------------------------------------------------
  // Détection utilisateur connecté
  // ------------------------------------------------------------------
  fetch(`${API_BASE}/check_auth.php`, { credentials: "include" })
    .then((res) => res.json())
    .then((data) => {
      if (data.logged_in) {
        connectedUsername.textContent = data.username || "toi";
        formConnexion.classList.add("hidden");
        formInscription.classList.add("hidden");
        connectedBlock.classList.remove("hidden");
      }
    })
    .catch(() => {
      /* L'API est injoignable : on laisse le formulaire de connexion affiché. */
    });

  // ------------------------------------------------------------------
  // Toast
  // ------------------------------------------------------------------
  let toastTimer = null;

  function showToast(title, message, type = "info", duration = 3500) {
    const wrapper = document.getElementById("biscord-toast");
    document.getElementById("toast-title").textContent = title;
    document.getElementById("toast-text").textContent = message;

    wrapper.classList.remove("toast-error", "toast-success");
    if (type === "error") wrapper.classList.add("toast-error");
    if (type === "success") wrapper.classList.add("toast-success");

    wrapper.classList.remove("hidden");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => wrapper.classList.add("hidden"), duration);
  }

  // ------------------------------------------------------------------
  // Force du mot de passe
  // ------------------------------------------------------------------
  function getPasswordStrength(pwd) {
    let strength = 0;
    if (pwd.length >= 8) strength += 30;
    if (/[A-Z]/.test(pwd)) strength += 20;
    if (/[a-z]/.test(pwd)) strength += 10;
    if (/[0-9]/.test(pwd)) strength += 20;
    if (/[^A-Za-z0-9]/.test(pwd)) strength += 20;
    return Math.min(strength, 100);
  }

  passwordInput.addEventListener("input", () => {
    const strength = getPasswordStrength(passwordInput.value);
    strengthBar.style.width = strength + "%";

    if (strength < 30) strengthBar.style.background = "#f23f43";
    else if (strength < 60) strengthBar.style.background = "#f0b232";
    else if (strength < 80) strengthBar.style.background = "#23a55a";
    else strengthBar.style.background = "#00a8fc";
  });

  // ------------------------------------------------------------------
  // Bascule connexion <-> inscription
  // ------------------------------------------------------------------
  window.echangerFormulaire = function () {
    if (formInscription.classList.contains("hidden")) {
      formConnexion.classList.add("hidden");
      formInscription.classList.remove("hidden");
    } else {
      formInscription.classList.add("hidden");
      formConnexion.classList.remove("hidden");
    }
  };

  function setSubmitting(form, isSubmitting, loadingLabel) {
    const submit = form.querySelector('input[type="submit"]');
    if (!submit) return;
    if (isSubmitting) {
      submit.dataset.label = submit.value;
      submit.value = loadingLabel;
      submit.disabled = true;
    } else {
      submit.value = submit.dataset.label || submit.value;
      submit.disabled = false;
    }
  }

  // ------------------------------------------------------------------
  // Inscription
  // ------------------------------------------------------------------
  formInscription.addEventListener("submit", async (e) => {
    e.preventDefault();
    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("mdp").value;
    const confirmation = document.getElementById("confirmation").value;

    if (!username || !email || !password) {
      showToast("Champs manquants", "Remplis tous les champs obligatoires.", "error");
      return;
    }

    if (getPasswordStrength(password) < 60) {
      showToast("Mot de passe faible", "Choisis un mot de passe plus fort !", "error");
      return;
    }

    if (password !== confirmation) {
      showToast("Erreur", "Les mots de passe ne correspondent pas.", "error");
      return;
    }

    setSubmitting(formInscription, true, "Création du compte…");

    try {
      const res = await fetch(`${API_BASE}/register.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ username, email, password }),
      });

      let data;
      try {
        data = await res.json();
      } catch {
        showToast("Erreur", "Réponse invalide du serveur.", "error");
        return;
      }

      if (data.success) {
        showToast("Inscription réussie !", "Redirection en cours…", "success");
        setTimeout(() => {
          window.location.href = "/accueil.html";
        }, 1500);
      } else {
        showToast("Erreur", data.error || "Inscription impossible.", "error");
      }
    } catch {
      showToast("Erreur réseau", "Impossible de joindre l'API.", "error");
    } finally {
      setSubmitting(formInscription, false);
    }
  });

  // ------------------------------------------------------------------
  // Connexion
  // ------------------------------------------------------------------
  formConnexion.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("identifiant").value.trim();
    const password = document.getElementById("mdp-connexion").value;

    if (!username || !password) {
      showToast("Champs manquants", "Identifiant et mot de passe sont obligatoires.", "error");
      return;
    }

    setSubmitting(formConnexion, true, "Connexion…");

    try {
      const res = await fetch(`${API_BASE}/login.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ username, password }),
      });

      let data;
      try {
        data = await res.json();
      } catch {
        showToast("Erreur", "Réponse du serveur invalide.", "error");
        return;
      }

      if (data.success) {
        showToast("Connexion réussie !", "Redirection en cours…", "success");
        setTimeout(() => {
          window.location.href = "/accueil.html";
        }, 1200);
      } else {
        showToast("Erreur", data.error || "Échec de la connexion.", "error");
      }
    } catch {
      showToast("Erreur réseau", "Impossible de joindre l'API.", "error");
    } finally {
      setSubmitting(formConnexion, false);
    }
  });

  // ------------------------------------------------------------------
  // État des services (carte de statut)
  // ------------------------------------------------------------------
  const SERVICES = [
    { key: "login", url: `${API_BASE}/check_auth.php` },
    { key: "send", url: `${API_BASE}/health.php` },
  ];
  const MAX_TICKS = 30;
  const pingStats = { login: [], send: [] };
  const lastStatus = { login: null, send: null };

  function getTimeString(date) {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit", second: "2-digit" });
  }

  // Pré-remplit chaque barre avec des segments neutres pour une largeur stable.
  SERVICES.forEach(({ key }) => {
    const bar = document.getElementById(`ping-history-${key}`);
    for (let i = 0; i < MAX_TICKS; i++) {
      const tick = document.createElement("div");
      tick.className = "tick";
      bar.appendChild(tick);
    }
    document.getElementById(`ping-start-${key}`).textContent =
      "Surveillé depuis " + getTimeString(new Date());
  });

  function pushTick(key, success, duration) {
    const bar = document.getElementById(`ping-history-${key}`);
    const tick = document.createElement("div");
    tick.className = `tick ${success ? "tick-ok" : "tick-fail"}`;
    tick.title = success ? `Réponse en ${Math.round(duration)} ms` : "Pas de réponse";
    bar.appendChild(tick);
    while (bar.children.length > MAX_TICKS) {
      bar.removeChild(bar.firstChild);
    }
  }

  function updateAverage(key) {
    const values = pingStats[key];
    if (!values.length) return;
    const avg = Math.round(values.reduce((a, b) => a + b, 0) / values.length);
    document.getElementById(`ping-average-${key}`).textContent = `${avg} ms`;
  }

  function updateOverallPill() {
    const pill = document.getElementById("overall-status");
    const text = document.getElementById("overall-status-text");
    const states = Object.values(lastStatus);

    pill.classList.remove("pill-ok", "pill-warn", "pill-fail");

    if (states.some((s) => s === null)) {
      text.textContent = "Vérification…";
      return;
    }
    if (states.every(Boolean)) {
      pill.classList.add("pill-ok");
      text.textContent = "Tous les services opérationnels";
    } else if (states.some(Boolean)) {
      pill.classList.add("pill-warn");
      text.textContent = "Service partiellement dégradé";
    } else {
      pill.classList.add("pill-fail");
      text.textContent = "Panne en cours";
    }
  }

  async function pingService({ key, url }) {
    const dot = document.getElementById(`status-${key}`);
    const start = performance.now();
    let responded = false;
    let time = 0;

    try {
      const res = await fetch(url, {
        method: "GET",
        credentials: "include",
        headers: { "X-Internal-Ping": "1" },
      });
      time = performance.now() - start;
      await res.json();
      responded = true;
    } catch {
      responded = false;
    }

    dot.classList.toggle("status-ok", responded);
    dot.classList.toggle("status-fail", !responded);
    pushTick(key, responded, time);

    if (responded) {
      pingStats[key].push(time);
      if (pingStats[key].length > MAX_TICKS) pingStats[key].shift();
      updateAverage(key);
    }

    lastStatus[key] = responded;
    updateOverallPill();
  }

  function pingAllServices() {
    SERVICES.forEach(pingService);
  }

  pingAllServices();
  setInterval(pingAllServices, 5000);
});
