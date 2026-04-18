export class DmPageController {
  constructor({ apiClient, store, view, win = window }) {
    this.apiClient = apiClient;
    this.store = store;
    this.view = view;
    this.win = win;
  }

  init() {
    const cid = new URLSearchParams(this.win.location.search).get("cid");

    if (!cid) {
      this.view.showMissingConversationError();
      throw new Error("BISCORD-800");
    }

    this.store.setConversationId(cid);

    this.view.bindSubmit(async (event) => {
      event.preventDefault();
      const content = this.view.getInputContent();
      if (!content) {
        return;
      }

      await this.apiClient.sendMessage(this.store.conversationId, content);
      this.view.clearInput();
      await this.loadMessages();
    });

    this.loadMessages();
    this.win.setInterval(() => this.loadMessages(), 4000);
  }

  async loadMessages() {
    const data = await this.apiClient.getMessages(this.store.conversationId);
    this.store.setMessages(data.messages);
    this.store.setRecipient(data.recipient);

    if (!data.messages) {
      return;
    }

    this.view.renderRecipient(this.store.recipient);
    this.view.renderMessages(this.store.messages);
  }
}
