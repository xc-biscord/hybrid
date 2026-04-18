export class DmStore {
  constructor(conversationId = null) {
    this.conversationId = conversationId;
    this.messages = [];
    this.recipient = null;
  }

  setConversationId(conversationId) {
    this.conversationId = conversationId;
  }

  setMessages(messages) {
    this.messages = Array.isArray(messages) ? messages : [];
  }

  setRecipient(recipient) {
    this.recipient = recipient;
  }
}
