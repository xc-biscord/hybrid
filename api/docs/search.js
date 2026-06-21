(function () {
  const docs = window.BISCORD_API_DOCS;
  const state = { lang: "fr", query: "" };

  function escapeHtml(value) {
    return String(value)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;");
  }

  function asCode(value) {
    if (value === undefined) return "";
    if (typeof value === "string") return value;
    return JSON.stringify(value, null, 2);
  }

  function slug(path) {
    return path.split("?")[0].replace(".php", "").replaceAll("_", "-");
  }

  function label(text) {
    return state.lang === "fr" ? text.fr : text.en;
  }

  function authLabel(auth) {
    const map = {
      public: { fr: "Public", en: "Public" },
      optional: { fr: "Session optionnelle", en: "Optional session" },
      session: { fr: "Session requise", en: "Session required" },
      P1: { fr: "P1 requis", en: "P1 required" }
    };
    return label(map[auth] || { fr: auth, en: auth });
  }

  function renderParams(endpoint) {
    if (!endpoint.query || endpoint.query.length === 0) return "";
    const heading = state.lang === "fr" ? "Paramètres de requête" : "Query parameters";
    const rows = endpoint.query.map((param) => `
      <tr>
        <td><code>${escapeHtml(param.name)}</code></td>
        <td>${escapeHtml(param.type)}</td>
        <td>${param.required ? "yes" : "no"}</td>
      </tr>
    `).join("");
    return `
      <h4>${heading}</h4>
      <table>
        <thead><tr><th>Name</th><th>Type</th><th>Required</th></tr></thead>
        <tbody>${rows}</tbody>
      </table>
    `;
  }

  function renderRequest(endpoint) {
    if (endpoint.request === undefined) return "";
    const type = endpoint.requestType === "form" ? "application/x-www-form-urlencoded" : "application/json";
    const title = state.lang === "fr" ? "Corps de requête" : "Request body";
    return `
      <h4>${title} <span class="muted">${type}</span></h4>
      <pre><code>${escapeHtml(asCode(endpoint.request))}</code></pre>
    `;
  }

  function renderResponses(endpoint) {
    const title = state.lang === "fr" ? "Réponses" : "Responses";
    return `
      <h4>${title}</h4>
      ${endpoint.responses.map((response) => `
        <div class="response">
          <span class="status">${response.code}</span>
          <pre><code>${escapeHtml(asCode(response.body))}</code></pre>
        </div>
      `).join("")}
    `;
  }

  function renderEndpoint(endpoint) {
    const id = slug(endpoint.path);
    const summary = state.lang === "fr" ? endpoint.fr : endpoint.en;
    const notes = endpoint.notes ? (state.lang === "fr" ? endpoint.notes.fr : endpoint.notes.en) : "";
    return `
      <article class="endpoint-card" id="${id}" data-search="${escapeHtml(`${endpoint.method} ${endpoint.path} ${endpoint.fr} ${endpoint.en}`.toLowerCase())}">
        <div class="endpoint-head">
          <div>
            <span class="method ${endpoint.method.toLowerCase()}">${endpoint.method}</span>
            <code class="path">/api/${escapeHtml(endpoint.path)}</code>
          </div>
          <span class="auth">${escapeHtml(authLabel(endpoint.auth))}</span>
        </div>
        <p>${escapeHtml(summary)}</p>
        ${renderParams(endpoint)}
        ${renderRequest(endpoint)}
        ${renderResponses(endpoint)}
        ${notes ? `<p class="note">${escapeHtml(notes)}</p>` : ""}
      </article>
    `;
  }

  function renderNav() {
    const nav = document.getElementById("endpoint-nav");
    nav.innerHTML = docs.groups.map((group) => {
      const endpoints = docs.endpoints.filter((endpoint) => endpoint.group === group.id);
      return `
        <section>
          <h2>${escapeHtml(label(group))}</h2>
          ${endpoints.map((endpoint) => `<a href="#${slug(endpoint.path)}" data-search="${escapeHtml(`${endpoint.path} ${endpoint.fr} ${endpoint.en}`.toLowerCase())}"><span>${endpoint.method}</span>${escapeHtml(endpoint.path.split("?")[0])}</a>`).join("")}
        </section>
      `;
    }).join("");
  }

  function renderDocs() {
    const root = document.getElementById("docs-root");
    root.innerHTML = docs.groups.map((group) => {
      const endpoints = docs.endpoints.filter((endpoint) => endpoint.group === group.id);
      return `
        <section class="group" id="group-${group.id}">
          <h2>${escapeHtml(label(group))}</h2>
          ${endpoints.map(renderEndpoint).join("")}
        </section>
      `;
    }).join("");
    document.getElementById("endpoint-count").textContent = docs.endpoints.length;
  }

  function applyLanguage() {
    document.documentElement.lang = state.lang;
    document.querySelectorAll("[data-i18n-fr]").forEach((node) => {
      node.textContent = node.dataset[`i18n${state.lang === "fr" ? "Fr" : "En"}`];
    });
    document.querySelectorAll("[data-lang]").forEach((button) => {
      button.classList.toggle("active", button.dataset.lang === state.lang);
    });
    renderNav();
    renderDocs();
    filter();
  }

  function filter() {
    const query = state.query.trim().toLowerCase();
    document.querySelectorAll(".endpoint-card, #endpoint-nav a").forEach((node) => {
      node.hidden = query !== "" && !node.dataset.search.includes(query);
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-lang]").forEach((button) => {
      button.addEventListener("click", () => {
        state.lang = button.dataset.lang;
        applyLanguage();
      });
    });
    document.getElementById("search-input").addEventListener("input", (event) => {
      state.query = event.target.value;
      filter();
    });
    applyLanguage();
  });
})();
