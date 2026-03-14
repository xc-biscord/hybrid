const API_BASE = "https://biscord-api-stg.xcsoftworks.com/api";
const serverId = new URLSearchParams(window.location.search).get('id');
let currentChannelId = null;

let myRole = null;
let isP1 = false;
let currentUserId = null;
let ctrlBActif = false;

window.addEventListener("keydown", (e) => {
  if (e.ctrlKey && e.key === "b") {
    if (!ctrlBActif) {
      ctrlBActif = true;
      chargerMessages();
    }
  }
});

window.addEventListener("keyup", (e) => {
  if (e.key === "Control" || e.key === "b") {
    if (ctrlBActif) {
      ctrlBActif = false;
      chargerMessages();
    }
  }
});

async function chargerProfilActuel() {
  const res = await fetch(`${API_BASE}/get_profile.php`, { credentials: "include" });
  const data = await res.json();
  if (data.success) {
    const u = data.profile;
    currentUserId = u.id;
    isP1 = u.is_p1 === true;
    document.getElementById("user-username").textContent = u.username;
    document.getElementById("user-status").textContent = u.status || "En ligne";
    document.getElementById("user-avatar").src = u.avatar_url || "https://biscord-api-stg.xcsoftworks.com/assets/default.png";
  }
}

async function chargerMonRoleServeur() {
  const res = await fetch(`${API_BASE}/get_my_server_role.php?server_id=${serverId}`, {
    credentials: "include"
  });
  const data = await res.json();
  myRole = data.role;
}

async function chargerNomServeur() {
  const res = await fetch(`${API_BASE}/get_server_name.php?id=${serverId}`, { credentials: "include" });
  const data = await res.json();
  document.getElementById("sidebar-server-name").textContent = data.name;
}

async function chargerTousLesServeurs() {
  const res = await fetch(`${API_BASE}/get_servers.php`, { credentials: "include" });
  const data = await res.json();
  const list = document.getElementById("server-list");
  list.innerHTML = "";
  if (data.success) {
    data.servers.forEach(server => {
      const li = document.createElement("li");
      li.textContent = server.name;
      li.onclick = () => window.location.href = `serveur.html?id=${server.id}`;
      list.appendChild(li);
    });
  } else {
    list.innerHTML = "<li>Aucun serveur trouvé.</li>";
  }
}

async function genererLienInvitation() {
  const formData = new FormData();
  formData.append('server_id', serverId);

  const res = await fetch(`${API_BASE}/create_invite.php`, {
    method: "POST",
    credentials: "include",
    body: formData
  });

  const data = await res.json();
  if (data.success) {
    alert("✅ Lien généré !");
    prompt("Voici le lien d'invitation :", data.invite_url);
  } else {
    alert("Erreur : " + data.error);
  }
}

async function chargerChannels() {
  const res = await fetch(`${API_BASE}/get_channels.php?server_id=${serverId}`, { credentials: "include" });
  const data = await res.json();
  const list = document.getElementById("channel-list");
  list.innerHTML = "";
  data.channels.forEach(channel => {
    const li = document.createElement("li");
    li.textContent = "#" + channel.name;
    li.onclick = () => {
      currentChannelId = channel.id;
      document.getElementById("current-channel-name").textContent = "#" + channel.name;
      chargerMessages();
    };
    list.appendChild(li);
  });
}

async function chargerMembres() {
  const res = await fetch(`${API_BASE}/get_users_in_server.php?server_id=${serverId}`, { credentials: "include" });
  const data = await res.json();
  const list = document.getElementById("member-list");
  list.innerHTML = "";

  data.users.forEach(user => {
    const li = document.createElement("li");
    li.className = "member-entry";

    const info = document.createElement("div");
    info.className = "member-info";

    let roleDisplay = user.role;
    let roleClass = "";

    if (user.role === "P1") {
      roleDisplay = "Fondateur";
      roleClass = "role-p1";
    } else if (user.role === "P2") {
      roleDisplay = "Administrateur";
      roleClass = "role-p2";
    } else if (user.role === "P3") {
      roleDisplay = "Modérateur";
      roleClass = "role-p3";
    }

    info.innerHTML = `<span>${user.username}</span> <span class="role-label ${roleClass}">${roleDisplay}</span>`;
    li.appendChild(info);

    if ((isP1 || myRole === "P2") && user.id !== currentUserId) {
      const actions = document.createElement("div");
      actions.className = "admin-actions";

      const kickBtn = document.createElement("button");
      kickBtn.textContent = "Kick";
      kickBtn.onclick = (e) => {
        e.stopPropagation();
        kickUser(user.id);
      };

      const roleBtn = document.createElement("button");
      roleBtn.textContent = "⇅ Rôle";
      roleBtn.onclick = (e) => {
        e.stopPropagation();
        changerRole(user.id);
      };

      actions.appendChild(kickBtn);
      actions.appendChild(roleBtn);
      li.appendChild(actions);
    }

    li.onclick = () => ouvrirProfil(user.id);
    list.appendChild(li);
  });
}

async function kickUser(userId) {
  if (!confirm("Confirmer l'expulsion ?")) return;
  const res = await fetch(`${API_BASE}/kick_member.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ target_user_id: userId, server_id: serverId })
  });
  const data = await res.json();
  if (data.success) {
    alert("Utilisateur expulsé !");
    chargerMembres();
  } else {
    alert("Erreur : " + data.error);
  }
}

async function changerRole(userId) {
  const nouveau = prompt("Nouveau rôle ? (P2, P3, member)");
  if (!["P2", "P3", "member"].includes(nouveau)) return;
  const res = await fetch(`${API_BASE}/set_member_role.php`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ target_user_id: userId, server_id: serverId, new_role: nouveau })
  });
  const data = await res.json();
  if (data.success) {
    alert("Rôle modifié !");
    chargerMembres();
  } else {
    alert("Erreur : " + data.error);
  }
}

function ouvrirProfil(userId) {
  fetch(`${API_BASE}/get_user_profile.php?user_id=${userId}`, { credentials: "include" })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const u = data.user;
        document.getElementById("profile-name").textContent = u.username;
        document.getElementById("profile-status").textContent = u.status || "Aucun statut.";
        document.getElementById("profile-bio").textContent = u.bio || "Aucune bio.";
        document.getElementById("profile-avatar").src = u.avatar_url || "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png";

        const dmBtn = document.getElementById("dm-button");
        dmBtn.onclick = async () => {
          const res = await fetch(`${API_BASE}/start_dm.php`, {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ other_user_id: userId })
          });

          const data = await res.json();
          if (data.conversation_id) {
            window.location.href = `dm.html?cid=${data.conversation_id}`;
          } else {
            alert("Erreur : " + (data.error || "Impossible de démarrer la conversation"));
          }
        };

        document.getElementById("profile-modal").classList.remove("hidden");
      }
    });
}

async function chargerApercuDM() {
  const res = await fetch(`${API_BASE}/get_dm_notifications.php`, {
    credentials: "include"
  });
  const data = await res.json();
  const container = document.getElementById("dm-avatar-scroll");
  container.innerHTML = "";

  if (!data.unread_conversations) return;

  data.unread_conversations.forEach(conv => {
    const bubble = document.createElement("div");
    bubble.className = "dm-avatar-bubble";
    bubble.onclick = () => window.location.href = `dm.html?cid=${conv.conversation_id}`;

    const avatar = conv.avatar_url && conv.avatar_url.trim() !== ""
      ? conv.avatar_url
      : "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png";

    bubble.innerHTML = `
      <img src="${avatar}" alt="${conv.username}">
      <div class="dm-tooltip">${conv.username} • ${conv.unread_count} msg${conv.unread_count > 1 ? 's' : ''}</div>
    `;
    container.appendChild(bubble);
  });
}

async function chargerMessages() {
  if (!currentChannelId) return;
  const res = await fetch(`${API_BASE}/get_messages.php?channel_id=${currentChannelId}`, { credentials: "include" });
  const data = await res.json();
  const container = document.getElementById("message-container");
  container.innerHTML = "";
  let lastUserId = null;

  data.messages.forEach((msg) => {
    const isSameUser = msg.user_id === lastUserId;
    lastUserId = msg.user_id;

    const group = document.createElement("div");
    group.className = "message-group";
    group.style.marginTop = isSameUser ? "4px" : "12px";

    if (!isSameUser) {
      const header = document.createElement("div");
      header.className = "message-header";

      const avatar = document.createElement("img");
      avatar.className = "message-avatar";
      avatar.src = msg.avatar_url || "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png";

      const meta = document.createElement("div");
      meta.className = "message-meta";

      const username = document.createElement("span");
      username.className = "message-username";
      username.textContent = msg.username;

      meta.appendChild(username);
      header.appendChild(avatar);
      header.appendChild(meta);
      group.appendChild(header);
    }

    const content = document.createElement("div");
    content.className = "message-content";

    const text = document.createElement("div");
    text.className = "message-text";
    text.textContent = msg.content;

    const messageId = msg.id;
    const authorId = msg.user_id;
    const peutSupprimer = (isP1 || myRole === "P2" || myRole === "P3" || authorId === currentUserId);

    if (peutSupprimer && messageId && ctrlBActif) {
      const delBtn = document.createElement("span");
      delBtn.className = "delete-btn";
      delBtn.textContent = "🗑️";
      delBtn.onclick = async () => {
        if (!confirm("Supprimer ce message ?")) return;
        group.classList.add("fade-out");
        setTimeout(async () => {
          const res = await fetch(`${API_BASE}/delete_message.php`, {
            method: "POST",
            credentials: "include",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message_id: messageId })
          });
          const result = await res.json();
          if (result.success) {
            group.remove();
          } else {
            alert("Erreur : " + result.error);
            group.classList.remove("fade-out");
          }
        }, 300);
      };
      content.appendChild(delBtn);
    }

    content.appendChild(text);
    group.appendChild(content);
    container.appendChild(group);
  });
}

document.getElementById("send-message-form").addEventListener("submit", async e => {
  e.preventDefault();
  const content = document.getElementById("message-input").value;
  if (!currentChannelId || !content) return;

  await fetch(`${API_BASE}/send_message.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ channel_id: currentChannelId, content })
  });

  document.getElementById("message-input").value = "";
  chargerMessages();
});

document.getElementById("create-channel-form").addEventListener("submit", async e => {
  e.preventDefault();
  const name = document.getElementById("new-channel-name").value;
  if (!name) return;
  await fetch(`${API_BASE}/create_channel.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ server_id: serverId, name })
  });
  document.getElementById("new-channel-name").value = "";
  chargerChannels();
});

function fermerProfil() {
  document.getElementById("profile-modal").classList.add("hidden");
}

function toggleMenu() {
  const menu = document.getElementById("profile-menu");
  menu.classList.toggle("hidden");
}

function logout() {
  fetch(`${API_BASE}/logout.php`, { credentials: "include" })
    .then(() => window.location.href = "/");
}

async function verifierDMsNonLus() {
  const res = await fetch(`${API_BASE}/get_dm_notifications.php`, {
    credentials: "include"
  });
  const data = await res.json();
  const badge = document.getElementById("dm-badge");
  badge.classList.add("hidden");

  if (data.unread_conversations && data.unread_conversations.length > 0) {
    badge.classList.remove("hidden");
  }
}

async function initBiscordServeur() {
  await chargerProfilActuel();
  await chargerMonRoleServeur();
  chargerNomServeur();
  chargerTousLesServeurs();
  chargerChannels();
  chargerMembres();
  verifierDMsNonLus();
  setInterval(verifierDMsNonLus, 5000);
  chargerApercuDM();
  setInterval(chargerApercuDM, 5000);
}

initBiscordServeur();
