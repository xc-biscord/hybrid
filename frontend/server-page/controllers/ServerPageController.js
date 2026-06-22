import { serverApiClient as defaultApiClient } from "../api/ServerApiClient.js";
import { ServerStore } from "../stores/ServerStore.js";
import { ChannelListView } from "../views/ChannelListView.js";
import { MessageListView } from "../views/MessageListView.js";
import { MemberListView } from "../views/MemberListView.js";
import { ModerationController } from "./ModerationController.js";
import { DmNotifier } from "./DmNotifier.js";

const DEFAULT_PROFILE_AVATAR = "https://biscord-api-stg.xcsoftworks.com/assets/default.png";
const DEFAULT_USER_AVATAR = "https://biscord-api-stg.xcsoftworks.com/assets/default-user.png";

// N'autorise que des URLs http(s) ; neutralise les schémas dangereux
// (javascript:, data:) issus de données utilisateur avant affectation à .src.
function safeImageUrl(value, fallback) {
  if (typeof value !== "string" || value === "") {
    return fallback;
  }
  try {
    const url = new URL(value, window.location.href);
    return url.protocol === "http:" || url.protocol === "https:" ? url.href : fallback;
  } catch {
    return fallback;
  }
}

export class ServerPageController {
  constructor({ apiClient = defaultApiClient } = {}) {
    const serverId = new URLSearchParams(window.location.search).get("id");

    this.apiClient = apiClient;
    this.store = new ServerStore(serverId);

    this.channelListView = new ChannelListView({
      listSelector: "#channel-list",
      currentChannelNameSelector: "#current-channel-name"
    });

    this.messageListView = new MessageListView({ containerSelector: "#message-container" });
    this.memberListView = new MemberListView({ listSelector: "#member-list" });

    this.dmNotifier = new DmNotifier({
      apiClient: this.apiClient,
      badgeSelector: "#dm-badge",
      avatarContainerSelector: "#dm-avatar-scroll"
    });

    this.moderationController = new ModerationController({
      apiClient: this.apiClient,
      store: this.store,
      onMembersUpdated: () => this.loadMembers()
    });
  }

  bindGlobalHandlers() {
    window.addEventListener("keydown", (event) => {
      if (event.ctrlKey && event.key === "b" && !this.store.ctrlBActif) {
        this.store.setCtrlBActif(true);
        this.loadMessages();
      }
    });

    window.addEventListener("keyup", (event) => {
      if ((event.key === "Control" || event.key === "b") && this.store.ctrlBActif) {
        this.store.setCtrlBActif(false);
        this.loadMessages();
      }
    });

    document.getElementById("send-message-form").addEventListener("submit", async (event) => {
      event.preventDefault();
      const input = document.getElementById("message-input");
      const content = input.value;

      if (!this.store.currentChannelId || !content) {
        return;
      }

      await this.apiClient.sendMessage(this.store.currentChannelId, content);
      input.value = "";
      this.loadMessages();
    });

    document.getElementById("create-channel-form").addEventListener("submit", async (event) => {
      event.preventDefault();
      const input = document.getElementById("new-channel-name");
      const name = input.value;

      if (!name) {
        return;
      }

      await this.apiClient.createChannel(this.store.serverId, name);
      input.value = "";
      this.loadChannels();
    });
  }

  async loadCurrentProfile() {
    const data = await this.apiClient.getCurrentProfile();
    if (!data.success) {
      return;
    }

    const profile = data.profile;
    this.store.setCurrentProfile(profile);

    document.getElementById("user-username").textContent = profile.username;
    document.getElementById("user-status").textContent = profile.status || "En ligne";
    document.getElementById("user-avatar").src = safeImageUrl(profile.avatar_url, DEFAULT_PROFILE_AVATAR);
  }

  async loadMyServerRole() {
    const data = await this.apiClient.getMyRole(this.store.serverId);
    this.store.setMyRole(data.role);
  }

  async loadServerName() {
    const data = await this.apiClient.getServerName(this.store.serverId);
    document.getElementById("sidebar-server-name").textContent = data.name;
  }

  async loadServers() {
    const data = await this.apiClient.getServers();
    const list = document.getElementById("server-list");

    list.innerHTML = "";
    if (!data.success) {
      list.innerHTML = "<li>Aucun serveur trouvé.</li>";
      return;
    }

    data.servers.forEach((server) => {
      const li = document.createElement("li");
      li.textContent = server.name;
      li.onclick = () => {
        window.location.href = `serveur.html?id=${server.id}`;
      };

      list.appendChild(li);
    });
  }

  async loadChannels() {
    const data = await this.apiClient.getChannels(this.store.serverId);
    this.channelListView.render(data.channels, (channel) => {
      this.store.setCurrentChannel(channel.id);
      this.loadMessages();
    });
  }

  async loadMessages() {
    if (!this.store.currentChannelId) {
      return;
    }

    const data = await this.apiClient.getMessages(this.store.currentChannelId);
    this.messageListView.render(data.messages, {
      ctrlBActif: this.store.ctrlBActif,
      canDeleteMessage: (authorId) => this.store.canDeleteMessage(authorId),
      onDelete: (messageId, groupElement) => this.moderationController.deleteMessage(messageId, groupElement)
    });
  }

  async loadMembers() {
    const data = await this.apiClient.getMembers(this.store.serverId);
    this.memberListView.render(data.users, {
      canModerate: this.store.canModerateMembers(),
      currentUserId: this.store.currentUserId,
      onKick: (userId) => this.moderationController.kickUser(userId),
      onRoleChange: (userId) => this.moderationController.changeRole(userId),
      onProfileOpen: (userId) => this.openProfile(userId)
    });
  }

  async openProfile(userId) {
    const data = await this.apiClient.getUserProfile(userId);
    if (!data.success) {
      return;
    }

    const user = data.user;
    document.getElementById("profile-name").textContent = user.username;
    document.getElementById("profile-status").textContent = user.status || "Aucun statut.";
    document.getElementById("profile-bio").textContent = user.bio || "Aucune bio.";
    document.getElementById("profile-avatar").src = user.avatar_url || DEFAULT_USER_AVATAR;

    const dmButton = document.getElementById("dm-button");
    dmButton.onclick = async () => {
      const dmData = await this.apiClient.startDm(userId);
      if (dmData.conversation_id) {
        window.location.href = `dm.html?cid=${dmData.conversation_id}`;
        return;
      }

      window.alert(`Erreur : ${dmData.error || "Impossible de démarrer la conversation"}`);
    };

    document.getElementById("profile-modal").classList.remove("hidden");
  }

  closeProfile() {
    document.getElementById("profile-modal").classList.add("hidden");
  }

  toggleMenu() {
    document.getElementById("profile-menu").classList.toggle("hidden");
  }

  logout() {
    this.apiClient.logout().then(() => {
      window.location.href = "/";
    });
  }

  async generateInviteLink() {
    const data = await this.apiClient.createInvite(this.store.serverId);

    if (!data.success) {
      window.alert(`Erreur : ${data.error}`);
      return;
    }

    window.alert("✅ Lien généré !");
    window.prompt("Voici le lien d'invitation :", data.invite_url);
  }

  async init() {
    this.bindGlobalHandlers();

    await this.loadCurrentProfile();
    await this.loadMyServerRole();

    this.loadServerName();
    this.loadServers();
    this.loadChannels();
    this.loadMembers();

    this.dmNotifier.startPolling(5000);
  }
}
