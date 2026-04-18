const DEFAULT_AVATAR_URL = "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png";

export class DmNotifier {
  constructor({ apiClient, badgeSelector, avatarContainerSelector }) {
    this.apiClient = apiClient;
    this.badgeElement = document.querySelector(badgeSelector);
    this.avatarContainerElement = document.querySelector(avatarContainerSelector);
  }

  async refreshBadge() {
    const data = await this.apiClient.getDmNotifications();

    this.badgeElement.classList.add("hidden");
    if (data.unread_conversations && data.unread_conversations.length > 0) {
      this.badgeElement.classList.remove("hidden");
    }
  }

  async refreshPreview() {
    const data = await this.apiClient.getDmNotifications();

    this.avatarContainerElement.innerHTML = "";
    if (!data.unread_conversations) {
      return;
    }

    data.unread_conversations.forEach((conversation) => {
      const bubble = document.createElement("div");
      bubble.className = "dm-avatar-bubble";
      bubble.onclick = () => {
        window.location.href = `dm.html?cid=${conversation.conversation_id}`;
      };

      const avatar = conversation.avatar_url && conversation.avatar_url.trim() !== ""
        ? conversation.avatar_url
        : DEFAULT_AVATAR_URL;

      bubble.innerHTML = `
        <img src="${avatar}" alt="${conversation.username}">
        <div class="dm-tooltip">${conversation.username} • ${conversation.unread_count} msg${conversation.unread_count > 1 ? "s" : ""}</div>
      `;

      this.avatarContainerElement.appendChild(bubble);
    });
  }

  startPolling(intervalMs = 5000) {
    this.refreshBadge();
    this.refreshPreview();

    window.setInterval(() => {
      this.refreshBadge();
    }, intervalMs);

    window.setInterval(() => {
      this.refreshPreview();
    }, intervalMs);
  }
}
