import { apiClient as defaultApiClient } from "../api/client.js";
import { ProfilePageView } from "../views/ProfilePageView.js";

export class ProfilePageController {
  constructor({ apiClient = defaultApiClient, view } = {}) {
    this.apiClient = apiClient;
    this.view =
      view ||
      new ProfilePageView({
        usernameSelector: "#username",
        avatarSelector: "#avatar",
        profileFormSelector: "#form-profile",
        accountFormSelector: "#form-account",
        passwordFormSelector: "#form-password",
        bioSelector: "#new-bio",
        avatarInputSelector: "#new-avatar",
        statusSelector: "#new-status",
        usernameInputSelector: "#new-username",
        emailInputSelector: "#new-email",
        newPasswordSelector: "#new-password",
        confirmPasswordSelector: "#confirm-password",
        currentPasswordSelector: "#current-password",
        toastWrapperSelector: "#biscord-toast",
        toastTitleSelector: "#toast-title",
        toastTextSelector: "#toast-text"
      });
  }

  init() {
    this.view.bindProfileSubmit((payload) => {
      this.updateProfile(payload);
    });

    this.view.bindAccountSubmit((payload) => {
      this.updateAccount(payload);
    });

    this.view.bindPasswordSubmit((payload) => {
      this.updatePassword(payload);
    });

    this.loadProfile();
  }

  async loadProfile() {
    try {
      const data = await this.apiClient.get("/get_profile.php");

      if (data.success) {
        this.view.renderProfile(data.profile);
        return;
      }

      this.view.showToast("Erreur", data.error || "Impossible de charger le profil.");
    } catch (error) {
      this.view.showToast("Erreur réseau", "Impossible de contacter le serveur.");
    }
  }

  async updateProfile(payload) {
    try {
      const data = await this.apiClient.post("/update_profile.php", payload);

      if (data.success) {
        this.view.showToast("✅ Profil mis à jour", "Tout est enregistré !");
        this.loadProfile();
        return;
      }

      this.view.showToast("Erreur", data.error);
    } catch (error) {
      this.view.showToast("Erreur réseau", "Impossible de contacter le serveur.");
    }
  }

  async updateAccount(payload) {
    try {
      const data = await this.apiClient.post("/update_account.php", payload);

      if (data.success) {
        this.view.showToast("✅ Compte mis à jour avec succès", "Synchronisation en cours !");
        this.loadProfile();
        return;
      }

      this.view.showToast("Erreur", data.error);
    } catch (error) {
      this.view.showToast("Erreur", "Connexion impossible au serveur.");
    }
  }

  async updatePassword({ password, confirm, current_password }) {
    if (password && password !== confirm) {
      this.view.showToast("Erreur", "Les nouveaux mots de passe ne correspondent pas.");
      return;
    }

    if (password && !current_password) {
      this.view.showToast("Erreur", "Tu dois entrer ton mot de passe actuel.");
      return;
    }

    try {
      const data = await this.apiClient.post("/update_account.php", {
        password,
        current_password
      });

      if (data.success) {
        this.view.showToast("✅ Mot de passe changé avec succès !", "Synchronisation en cours...");
        return;
      }

      this.view.showToast("Erreur", data.error);
    } catch (error) {
      this.view.showToast("Erreur", "Serveur injoignable.");
    }
  }
}

const controller = new ProfilePageController();
controller.init();
