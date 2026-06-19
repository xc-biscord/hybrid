-- --------------------------------------------------------
-- Table `user_passkeys` — PoC WebAuthn / Passkeys (évolution expérimentale).
--
-- À appliquer sur la base Biscord (provisioning par SQL brut, comme le reste du
-- schéma). N'enregistre QUE des données publiques : la clé privée ne quitte
-- jamais l'authentificateur (téléphone, clé FIDO2, Touch ID...). On stocke la
-- clé PUBLIQUE COSE, l'identifiant public du credential et un compteur anti-rejeu.
--
-- L'utilisateur applicatif n'a en général que les droits CRUD ; la création de
-- table nécessite un compte privilégié, puis :
--   GRANT SELECT, INSERT, UPDATE, DELETE ON <db>.user_passkeys TO '<app_user>'@'localhost';
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_passkeys` (
  `id`            int NOT NULL AUTO_INCREMENT,
  `user_id`       int NOT NULL,
  `credential_id` varchar(512) NOT NULL COMMENT 'base64url, identifiant public du credential',
  `public_key`    text NOT NULL COMMENT 'cle PUBLIQUE COSE (base64) — jamais de cle privee',
  `sign_count`    bigint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'compteur de signature anti-clonage',
  `name`          varchar(100) NOT NULL COMMENT 'nom donne par l''utilisateur',
  `user_handle`   varchar(64) NOT NULL COMMENT 'lien credential -> user (assertion WebAuthn)',
  `transports`    varchar(255) DEFAULT NULL COMMENT 'JSON optionnel : usb, internal, hybrid...',
  `aaguid`        varchar(64) DEFAULT NULL COMMENT 'identifiant modele d''authentificateur',
  `created_at`    timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at`  timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_credential_id` (`credential_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `user_passkeys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
