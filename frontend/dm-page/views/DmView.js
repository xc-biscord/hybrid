const DEFAULT_AVATAR = "https://biscord-api-stg.xcsoftworks.com/assets/default.png";

// N'autorise que des URLs http(s) pour les avatars : neutralise les schémas
// dangereux (javascript:, data:) issus de données utilisateur.
function safeImageUrl(value) {
  if (typeof value !== "string" || value === "") {
    return DEFAULT_AVATAR;
  }
  try {
    const url = new URL(value, document.baseURI);
    return url.protocol === "http:" || url.protocol === "https:" ? url.href : DEFAULT_AVATAR;
  } catch {
    return DEFAULT_AVATAR;
  }
}

export class DmView {
  constructor(doc = document) {
    this.doc = doc;
    this.messagesContainer = this.doc.getElementById("dm-messages");
    this.username = this.doc.getElementById("dm-username");
    this.sidebarUsername = this.doc.getElementById("dm-user-name");
    this.avatar = this.doc.getElementById("dm-avatar");
    this.form = this.doc.getElementById("dm-form");
    this.input = this.doc.getElementById("dm-input");
  }

  showMissingConversationError() {
    this.doc.body.innerHTML = `
      <div style="padding: 2rem; text-align: center; font-size: 1.2rem; color: #ff4d4d; font-weight: bold; background-color: #121212;">
        🚫 BISCORD-800 : Pas de destinataire trouvé.<br>
        Veuillez sélectionner un utilisateur pour commencer une conversation privée.
      </div>`;
  }

  renderRecipient(recipient) {
    if (!recipient) {
      console.warn("⚠️ Aucun destinataire reçu dans la réponse de l'API !");
      return;
    }

    this.username.textContent = recipient.username || "???";
    this.sidebarUsername.textContent = recipient.username || "???";
    this.avatar.src = safeImageUrl(recipient.avatar_url);
  }

  renderMessages(messages) {
    this.messagesContainer.innerHTML = "";

    let lastSenderId = null;

    messages.forEach((msg) => {
      const isSameUser = msg.sender_id === lastSenderId;
      lastSenderId = msg.sender_id;

      if (!isSameUser) {
        const group = document.createElement("div");
        group.className = "message-group";

        const avatar = document.createElement("img");
        avatar.className = "message-avatar";
        avatar.src = safeImageUrl(msg.avatar);

        const contentGroup = document.createElement("div");
        contentGroup.className = "message-content-group";

        const header = document.createElement("div");
        header.className = "message-header";
        header.textContent = msg.username;

        const text = document.createElement("div");
        text.className = "message-text";
        text.textContent = msg.content;

        contentGroup.appendChild(header);
        contentGroup.appendChild(text);
        group.appendChild(avatar);
        group.appendChild(contentGroup);
        this.messagesContainer.appendChild(group);
      } else {
        const lastGroup = this.messagesContainer.lastElementChild.querySelector(".message-content-group");
        const text = document.createElement("div");
        text.classList.add("message-text");
        text.textContent = msg.content;
        lastGroup.appendChild(text);
      }
    });

    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
  }

  bindSubmit(handler) {
    this.form.addEventListener("submit", handler);
  }

  getInputContent() {
    return this.input.value.trim();
  }

  clearInput() {
    this.input.value = "";
  }
}
