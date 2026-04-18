const DEFAULT_API_BASE = "/api";

export class ServerApiClient {
  constructor(apiBase = DEFAULT_API_BASE) {
    this.apiBase = apiBase;
  }

  async get(path) {
    const response = await fetch(`${this.apiBase}${path}`, { credentials: "include" });
    return response.json();
  }

  async postJson(path, payload) {
    const response = await fetch(`${this.apiBase}${path}`, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    });

    return response.json();
  }

  async postForm(path, formData) {
    const response = await fetch(`${this.apiBase}${path}`, {
      method: "POST",
      credentials: "include",
      body: formData
    });

    return response.json();
  }

  getCurrentProfile() { return this.get("/get_profile.php"); }
  getMyRole(serverId) { return this.get(`/get_my_server_role.php?server_id=${serverId}`); }
  getServerName(serverId) { return this.get(`/get_server_name.php?id=${serverId}`); }
  getServers() { return this.get("/get_servers.php"); }
  createInvite(serverId) {
    const formData = new FormData();
    formData.append("server_id", serverId);
    return this.postForm("/create_invite.php", formData);
  }
  getChannels(serverId) { return this.get(`/get_channels.php?server_id=${serverId}`); }
  createChannel(serverId, name) { return this.postJson("/create_channel.php", { server_id: serverId, name }); }
  getMembers(serverId) { return this.get(`/get_users_in_server.php?server_id=${serverId}`); }
  kickMember(serverId, userId) { return this.postJson("/kick_member.php", { target_user_id: userId, server_id: serverId }); }
  changeRole(serverId, userId, role) {
    return this.postJson("/set_member_role.php", { target_user_id: userId, server_id: serverId, new_role: role });
  }
  getUserProfile(userId) { return this.get(`/get_user_profile.php?user_id=${userId}`); }
  startDm(userId) { return this.postJson("/start_dm.php", { other_user_id: userId }); }
  getDmNotifications() { return this.get("/get_dm_notifications.php"); }
  getMessages(channelId) { return this.get(`/get_messages.php?channel_id=${channelId}`); }
  sendMessage(channelId, content) { return this.postJson("/send_message.php", { channel_id: channelId, content }); }
  deleteMessage(messageId) { return this.postJson("/delete_message.php", { message_id: messageId }); }
  logout() { return fetch(`${this.apiBase}/logout.php`, { credentials: "include" }); }
}

export const serverApiClient = new ServerApiClient();
