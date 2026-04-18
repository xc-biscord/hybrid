const DEFAULT_AVATAR_URL = "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png";

export class MessageListView {
  constructor({ containerSelector }) {
    this.container = document.querySelector(containerSelector);
  }

  render(messages, { ctrlBActif, canDeleteMessage, onDelete }) {
    this.container.innerHTML = "";
    let lastUserId = null;

    messages.forEach((message) => {
      const isSameUser = message.user_id === lastUserId;
      lastUserId = message.user_id;

      const group = document.createElement("div");
      group.className = "message-group";
      group.style.marginTop = isSameUser ? "4px" : "12px";

      if (!isSameUser) {
        const header = document.createElement("div");
        header.className = "message-header";

        const avatar = document.createElement("img");
        avatar.className = "message-avatar";
        avatar.src = message.avatar_url || DEFAULT_AVATAR_URL;

        const meta = document.createElement("div");
        meta.className = "message-meta";

        const username = document.createElement("span");
        username.className = "message-username";
        username.textContent = message.username;

        meta.appendChild(username);
        header.appendChild(avatar);
        header.appendChild(meta);
        group.appendChild(header);
      }

      const content = document.createElement("div");
      content.className = "message-content";

      const text = document.createElement("div");
      text.className = "message-text";
      text.textContent = message.content;

      if (ctrlBActif && message.id && canDeleteMessage(message.user_id)) {
        const deleteButton = document.createElement("span");
        deleteButton.className = "delete-btn";
        deleteButton.textContent = "🗑️";
        deleteButton.onclick = () => onDelete(message.id, group);
        content.appendChild(deleteButton);
      }

      content.appendChild(text);
      group.appendChild(content);
      this.container.appendChild(group);
    });
  }
}
