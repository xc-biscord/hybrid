document.addEventListener("DOMContentLoaded", () => {
  const API_BASE = "/api";

  const welcomeScreen = document.getElementById("welcome-screen");
  const formInscription = document.getElementById("form-inscription");
  const formConnexion = document.getElementById("form-connexion");
  const connectedBlock = document.getElementById("connected-user-message");
  const connectedUsername = document.getElementById("connected-username");
  const loader = document.getElementById("loader");
  const passwordInput = document.getElementById("mdp");
  const strengthBar = document.querySelector(".strength-bar");

  // 🔐 Détection utilisateur connecté
  fetch(`${API_BASE}/check_auth.php`, { credentials: "include" })
    .then(res => res.json())
    .then(data => {
      if (data.logged_in) {
        connectedUsername.textContent = data.username;
        connectedBlock.classList.remove("hidden");
        welcomeScreen.classList.add("hidden");
      } else {
        welcomeScreen.classList.remove("hidden");
      }
    });

  // Toast custom
  function showToast(title, message, duration = 3000) {
    const wrapper = document.getElementById("biscord-toast");
    const titleEl = document.getElementById("toast-title");
    const textEl = document.getElementById("toast-text");

    titleEl.textContent = title;
    textEl.textContent = message;

    wrapper.classList.remove("hidden");

    setTimeout(() => {
      wrapper.classList.add("hidden");
    }, duration);
  }

  // Barre de force du mot de passe
  passwordInput.addEventListener("input", () => {
    const value = passwordInput.value;
    const strength = getPasswordStrength(value); // 0 à 100

    strengthBar.style.width = strength + "%";

    if (strength < 30) strengthBar.style.background = "red";
    else if (strength < 60) strengthBar.style.background = "orange";
    else if (strength < 80) strengthBar.style.background = "yellowgreen";
    else strengthBar.style.background = "#00cfff";
  });

  function getPasswordStrength(pwd) {
    let strength = 0;
    if (pwd.length >= 8) strength += 30;
    if (/[A-Z]/.test(pwd)) strength += 20;
    if (/[a-z]/.test(pwd)) strength += 10;
    if (/[0-9]/.test(pwd)) strength += 20;
    if (/[^A-Za-z0-9]/.test(pwd)) strength += 20;
    return Math.min(strength, 100);
  }

  // Animation de formulaires
  window.afficherFormulaire = function (type) {
    welcomeScreen.classList.add("fade-out");
    setTimeout(() => {
      welcomeScreen.classList.add("hidden");
      formInscription.classList.add("hidden");
      formConnexion.classList.add("hidden");

      const formToShow = type === "inscription" ? formInscription : formConnexion;
      formToShow.classList.remove("hidden");
      formToShow.classList.add("fade-in");
    }, 300);
  };

  window.echangerFormulaire = function () {
    if (!formInscription.classList.contains("hidden")) {
      formInscription.classList.add("hidden");
      formConnexion.classList.remove("hidden");
    } else {
      formConnexion.classList.add("hidden");
      formInscription.classList.remove("hidden");
    }
  };

  // Formulaire inscription
  formInscription.addEventListener("submit", async (e) => {
    e.preventDefault();
    const username = document.getElementById("username").value;
    const email = document.getElementById("email").value;
    const password = document.getElementById("mdp").value;
    const confirmation = document.getElementById("confirmation").value;

    if (getPasswordStrength(password) < 60) {
      showToast("Mot de passe faible", "Choisis un mot de passe plus fort !");
      return;
    }

    if (password !== confirmation) {
      showToast("Erreur", "Les mots de passe ne correspondent pas.");
      return;
    }

    loader.classList.remove("hidden");

    const res = await fetch(`${API_BASE}/register.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ username, email, password }),
    });

    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch (e) {
      showToast("Erreur", "Réponse invalide du serveur.");
      return;
    }

    if (data.success) {
      showToast("✅ Inscription réussie !", "Redirection en cours...");
      setTimeout(() => {
        window.location.href = "/accueil.html";
      }, 2000);
    } else {
      loader.classList.add("hidden");
      showToast("Erreur", data.error || "Inscription impossible.");
    }
  });

  // Formulaire connexion
  formConnexion.addEventListener("submit", async (e) => {
    e.preventDefault();

    const username = document.getElementById("identifiant").value;
    const password = document.getElementById("mdp-connexion").value;

    const res = await fetch(`${API_BASE}/login.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ username, password }),
    });

    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      showToast("Erreur", "Réponse du serveur invalide.");
      return;
    }

    if (data.success) {
      showToast("✅ Connexion réussie !", "Redirection en cours...");
      setTimeout(() => {
        window.location.href = "/accueil.html";
      }, 1500);
    } else {
      showToast("Erreur", data.error || "Échec de la connexion.");
    }
  });
});
