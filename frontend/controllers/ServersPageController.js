import { apiClient as defaultApiClient } from "../api/client.js";
import { ServersPageView } from "../views/ServersPageView.js";

export class ServersPageController {
  constructor({ apiClient = defaultApiClient, view } = {}) {
    this.apiClient = apiClient;
    this.view = view || new ServersPageView({
      serverListSelector: "#server-list",
      formSelector: "#create-server-form",
      serverNameSelector: "#server-name"
    });
  }

  init() {
    this.view.bindCreateServer((serverName) => {
      this.createServer(serverName);
    });

    this.loadServers();
  }

  async loadServers() {
    const data = await this.apiClient.get("/get_servers.php");

    if (data.success) {
      this.view.renderServers(data.servers, (server) => {
        this.view.goToServer(server.id);
      });
      return;
    }

    this.view.renderEmptyState();
  }

  async createServer(name) {
    const data = await this.apiClient.post("/create_server.php", { nom: name });

    if (data.success) {
      this.view.showCreateSuccess();
      this.view.resetCreateServerForm();
      this.loadServers();
      return;
    }

    this.view.showCreateError(data.error);
  }
}

const controller = new ServersPageController();
controller.init();
