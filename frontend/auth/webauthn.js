/**
 * Helpers WebAuthn partagés (PoC Passkeys).
 *
 * Le navigateur manipule des ArrayBuffer binaires, alors que le serveur échange
 * du JSON où ces champs sont encodés en base64url. Ce module fait le pont :
 *   - convertit les options reçues du serveur vers le format attendu par
 *     navigator.credentials.create()/get() ;
 *   - re-sérialise la réponse de l'authentificateur en JSON base64url pour
 *     l'envoyer au serveur.
 *
 * Aucune cryptographie ici : tout le secret reste dans l'authentificateur, et
 * la vérification se fait côté serveur (web-auth/webauthn-lib, ES256).
 *
 * Exposé en global `window.BiscordWebAuthn` pour être utilisable aussi bien par
 * les scripts classiques (index.js) que par les modules ES (profil).
 */
(function () {
  "use strict";

  function base64urlToBuffer(base64url) {
    const padding = "=".repeat((4 - (base64url.length % 4)) % 4);
    const base64 = (base64url + padding).replace(/-/g, "+").replace(/_/g, "/");
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
  }

  function bufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = "";
    for (let i = 0; i < bytes.length; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
  }

  // Retire les clés à valeur null/undefined d'un objet (copie superficielle).
  // Les options sérialisées côté serveur peuvent contenir des champs null (ex.
  // authenticatorSelection.authenticatorAttachment) que le navigateur "ignore"
  // en émettant un warning ; on les enlève pour des options propres.
  function stripNullValues(object) {
    const cleaned = {};
    for (const [key, value] of Object.entries(object)) {
      if (value !== null && value !== undefined) {
        cleaned[key] = value;
      }
    }
    return cleaned;
  }

  /** Prépare les options de création (enregistrement) pour le navigateur. */
  function prepareCreationOptions(options) {
    const publicKey = { ...options };
    publicKey.challenge = base64urlToBuffer(options.challenge);
    publicKey.user = { ...options.user, id: base64urlToBuffer(options.user.id) };

    // On préserve les valeurs valides de authenticatorSelection (residentKey,
    // userVerification...) et on retire seulement les champs null.
    if (options.authenticatorSelection) {
      publicKey.authenticatorSelection = stripNullValues(options.authenticatorSelection);
    }

    if (Array.isArray(options.excludeCredentials)) {
      publicKey.excludeCredentials = options.excludeCredentials.map((cred) => ({
        ...cred,
        id: base64urlToBuffer(cred.id),
      }));
    }
    return publicKey;
  }

  /** Prépare les options d'assertion (connexion) pour le navigateur. */
  function prepareRequestOptions(options) {
    const publicKey = { ...options };
    publicKey.challenge = base64urlToBuffer(options.challenge);

    if (Array.isArray(options.allowCredentials)) {
      publicKey.allowCredentials = options.allowCredentials.map((cred) => ({
        ...cred,
        id: base64urlToBuffer(cred.id),
      }));
    }
    return publicKey;
  }

  /** Sérialise une réponse d'attestation (création) en JSON base64url. */
  function serializeAttestation(credential) {
    const response = credential.response;
    return {
      id: credential.id,
      rawId: bufferToBase64url(credential.rawId),
      type: credential.type,
      response: {
        clientDataJSON: bufferToBase64url(response.clientDataJSON),
        attestationObject: bufferToBase64url(response.attestationObject),
      },
    };
  }

  /** Sérialise une réponse d'assertion (connexion) en JSON base64url. */
  function serializeAssertion(credential) {
    const response = credential.response;
    return {
      id: credential.id,
      rawId: bufferToBase64url(credential.rawId),
      type: credential.type,
      response: {
        clientDataJSON: bufferToBase64url(response.clientDataJSON),
        authenticatorData: bufferToBase64url(response.authenticatorData),
        signature: bufferToBase64url(response.signature),
        userHandle: response.userHandle ? bufferToBase64url(response.userHandle) : null,
      },
    };
  }

  /** WebAuthn est-il disponible dans ce navigateur / contexte sécurisé ? */
  function isSupported() {
    return typeof window.PublicKeyCredential !== "undefined" && !!navigator.credentials;
  }

  window.BiscordWebAuthn = {
    base64urlToBuffer,
    bufferToBase64url,
    prepareCreationOptions,
    prepareRequestOptions,
    serializeAttestation,
    serializeAssertion,
    isSupported,
  };
})();
