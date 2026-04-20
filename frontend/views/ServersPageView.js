export class ServersPageView {
  constructor({ serverListSelector, formSelector, serverNameSelector }) {
    this.serverListElement = document.querySelector(serverListSelector);
    this.formElement = document.querySelector(formSelector);
    this.serverNameElement = document.querySelector(serverNameSelector);
  }

  bindCreateServer(handler) {
    this.formElement.addEventListener("submit", (event) => {
      event.preventDefault();
      handler(this.serverNameElement.value);
    });
  }

  clearServers() {
    this.serverListElement.innerHTML = "";
  }

  renderServers(servers, onServerClick) {
    this.clearServers();

    if (!servers || servers.length === 0) {
      this.renderEmptyState();
      return;
    }

    servers.forEach((server) => {
      const serverElement = document.createElement("div");
      serverElement.className = "server-entry";
      serverElement.textContent = server.name;
      serverElement.onclick = () => onServerClick(server);
      this.serverListElement.appendChild(serverElement);
    });
  }

  renderEmptyState() {
    this.serverListElement.innerHTML = "<p class='no-server'>Aucun serveur pour l’instant.</p>";
  }

  resetCreateServerForm() {
    this.formElement.reset();
  }

  showCreateSuccess() {
    window.alert("Serveur créé !");
  }

  showCreateError(message) {
    window.alert(`Erreur : ${message}`);
  }

  goToServer(serverId) {
    window.location.href = `serveur?id=${serverId}`;
  }
}
