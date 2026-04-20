const DEFAULT_API_BASE = "/api";

export class ApiClient {
  constructor(apiBase = DEFAULT_API_BASE) {
    this.apiBase = apiBase;
  }

  async get(path) {
    const response = await fetch(`${this.apiBase}${path}`, {
      credentials: "include"
    });

    return response.json();
  }

  async post(path, payload) {
    const response = await fetch(`${this.apiBase}${path}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify(payload)
    });

    return response.json();
  }
}

export const apiClient = new ApiClient();
