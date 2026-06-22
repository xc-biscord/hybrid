import { apiClient } from "../../api/client.js";

export class DmApiClient {
  async getMessages(conversationId) {
    return apiClient.get(`/get_dm_messages.php?conversation_id=${encodeURIComponent(conversationId)}`);
  }

  async sendMessage(conversationId, content) {
    return apiClient.post("/send_dm.php", { conversation_id: conversationId, content });
  }
}
