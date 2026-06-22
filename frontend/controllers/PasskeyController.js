/**
 * Contrôleur de la section "Passkeys" du profil (PoC WebAuthn).
 *
 * Responsabilités :
 *   - lister les passkeys de l'utilisateur connecté ;
 *   - en ajouter une (navigator.credentials.create + vérification serveur) ;
 *   - en supprimer une (avec garde "dernière méthode" côté serveur).
 *
 * Toute la cryptographie est côté serveur ; ici on ne fait que de l'UI et des
 * appels HTTP. Les helpers binaires viennent de window.BiscordWebAuthn.
 */

const API_BASE = "/api";

const listElement = document.getElementById("passkeys-list");
const addButton = document.getElementById("btn-add-passkey");

// Toast partagé avec le reste de la page profil.
function showToast(title, message, duration = 3500) {
  const wrapper = document.getElementById("biscord-toast");
  if (!wrapper) {
    return;
  }
  document.getElementById("toast-title").textContent = title;
  document.getElementById("toast-text").textContent = message;
  wrapper.classList.remove("hidden");
  setTimeout(() => wrapper.classList.add("hidden"), duration);
}

function formatDate(value) {
  if (!value) {
    return "—";
  }
  const date = new Date(value.replace(" ", "T"));
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString();
}

// Affiche un message d'état (vide / erreur) sans interpréter de HTML : le texte
// peut provenir de l'API, on passe donc par textContent.
function renderEmptyState(message) {
  listElement.innerHTML = "";
  const empty = document.createElement("li");
  empty.className = "passkeys-empty";
  empty.textContent = message;
  listElement.appendChild(empty);
}

function renderPasskeys(passkeys) {
  listElement.innerHTML = "";

  if (!passkeys.length) {
    const empty = document.createElement("li");
    empty.className = "passkeys-empty";
    empty.textContent = "Aucune passkey enregistrée pour l'instant.";
    listElement.appendChild(empty);
    return;
  }

  for (const passkey of passkeys) {
    const item = document.createElement("li");
    item.className = "passkey-item";

    const info = document.createElement("div");
    info.className = "passkey-info";

    const name = document.createElement("strong");
    name.textContent = passkey.name;

    const meta = document.createElement("span");
    meta.className = "passkey-meta";
    meta.textContent =
      `Créée le ${formatDate(passkey.created_at)} · ` +
      `Dernière utilisation : ${formatDate(passkey.last_used_at)}`;

    info.appendChild(name);
    info.appendChild(meta);

    const deleteButton = document.createElement("button");
    deleteButton.type = "button";
    deleteButton.className = "passkey-delete";
    deleteButton.textContent = "🗑 Supprimer";
    deleteButton.addEventListener("click", () => deletePasskey(passkey.id, passkey.name));

    item.appendChild(info);
    item.appendChild(deleteButton);
    listElement.appendChild(item);
  }
}

async function loadPasskeys() {
  try {
    const res = await fetch(`${API_BASE}/passkey_list.php`, { credentials: "include" });
    const data = await res.json();
    if (data.success) {
      renderPasskeys(data.passkeys || []);
    } else {
      renderEmptyState(data.error || "Erreur de chargement.");
    }
  } catch {
    renderEmptyState("Impossible de charger les passkeys.");
  }
}

async function addPasskey() {
  const WA = window.BiscordWebAuthn;
  if (!WA || !WA.isSupported()) {
    showToast("Non supporté", "Les passkeys ne sont pas disponibles sur ce navigateur.");
    return;
  }

  const name = (prompt("Nom de cette passkey (ex. iPhone, clé YubiKey) :", "Ma passkey") || "").trim();
  if (!name) {
    return;
  }

  addButton.disabled = true;
  try {
    // 1) Demande un challenge de création au serveur.
    const optRes = await fetch(`${API_BASE}/passkey_register_options.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: "{}",
    });
    const optData = await optRes.json();
    if (!optData.success) {
      showToast("Erreur", optData.error || "Impossible de préparer l'enregistrement.");
      return;
    }

    // 2) Crée la paire de clés dans l'authentificateur (clé privée jamais exposée).
    const publicKey = WA.prepareCreationOptions(optData.options);
    let credential;
    try {
      credential = await navigator.credentials.create({ publicKey });
    } catch {
      showToast("Annulé", "Création de la passkey annulée ou impossible.");
      return;
    }

    // 3) Envoie l'attestation (clé publique) au serveur pour vérification + stockage.
    const verifyRes = await fetch(`${API_BASE}/passkey_register_verify.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ name, credential: WA.serializeAttestation(credential) }),
    });
    const verifyData = await verifyRes.json();

    if (verifyData.success) {
      showToast("Passkey ajoutée", `« ${name} » est prête à l'emploi.`);
      loadPasskeys();
    } else {
      showToast("Erreur", verifyData.error || "Enregistrement impossible.");
    }
  } catch {
    showToast("Erreur réseau", "Impossible de joindre l'API.");
  } finally {
    addButton.disabled = false;
  }
}

async function deletePasskey(id, name) {
  if (!confirm(`Supprimer la passkey « ${name} » ?`)) {
    return;
  }

  try {
    const res = await fetch(`${API_BASE}/passkey_delete.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id }),
    });
    const data = await res.json();

    if (data.success) {
      showToast("Passkey supprimée", `« ${name} » a été retirée.`);
      loadPasskeys();
    } else {
      // Couvre notamment la garde "dernière méthode de connexion" (HTTP 409).
      showToast("Suppression refusée", data.error || "Impossible de supprimer.");
    }
  } catch {
    showToast("Erreur réseau", "Impossible de joindre l'API.");
  }
}

if (addButton) {
  addButton.addEventListener("click", addPasskey);
  loadPasskeys();
}
