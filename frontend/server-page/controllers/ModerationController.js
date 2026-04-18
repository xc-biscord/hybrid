export class ModerationController {
  constructor({ apiClient, store, onMembersUpdated }) {
    this.apiClient = apiClient;
    this.store = store;
    this.onMembersUpdated = onMembersUpdated;
  }

  async kickUser(userId) {
    if (!window.confirm("Confirmer l'expulsion ?")) {
      return;
    }

    const data = await this.apiClient.kickMember(this.store.serverId, userId);
    if (data.success) {
      window.alert("Utilisateur expulsé !");
      this.onMembersUpdated();
      return;
    }

    window.alert(`Erreur : ${data.error}`);
  }

  async changeRole(userId) {
    const nextRole = window.prompt("Nouveau rôle ? (P2, P3, member)");
    if (!["P2", "P3", "member"].includes(nextRole)) {
      return;
    }

    const data = await this.apiClient.changeRole(this.store.serverId, userId, nextRole);
    if (data.success) {
      window.alert("Rôle modifié !");
      this.onMembersUpdated();
      return;
    }

    window.alert(`Erreur : ${data.error}`);
  }

  async deleteMessage(messageId, messageGroupElement) {
    if (!window.confirm("Supprimer ce message ?")) {
      return;
    }

    messageGroupElement.classList.add("fade-out");

    setTimeout(async () => {
      const result = await this.apiClient.deleteMessage(messageId);
      if (result.success) {
        messageGroupElement.remove();
        return;
      }

      window.alert(`Erreur : ${result.error}`);
      messageGroupElement.classList.remove("fade-out");
    }, 300);
  }
}
