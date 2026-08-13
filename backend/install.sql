-- ============================================================
-- BLITZ LEIHEN — Schéma MySQL (remplace MongoDB)
-- À importer une seule fois via phpMyAdmin (cPanel → Bases de
-- données → phpMyAdmin → onglet "Importer").
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Administrateurs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  avatar VARCHAR(255) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','superadmin','conseiller') NOT NULL DEFAULT 'conseiller',
  actif TINYINT(1) NOT NULL DEFAULT 1,
  login_tentatives_echouees INT NOT NULL DEFAULT 0,
  compte_verrouille TINYINT(1) NOT NULL DEFAULT 0,
  verrouillage_fin DATETIME NULL,
  derniere_connexion DATETIME NULL,
  derniere_connexion_ip VARCHAR(64) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jetons de session admin (remplace les tokens JWT)
CREATE TABLE IF NOT EXISTS admin_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  CONSTRAINT fk_token_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Demandes de crédit
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS demandes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference_number VARCHAR(20) NOT NULL UNIQUE,
  vorname VARCHAR(50) NOT NULL,
  nachname VARCHAR(50) NOT NULL,
  geburtsdatum DATE NOT NULL,
  staatsangehoerigkeit VARCHAR(20) NOT NULL DEFAULT 'deutsch',
  email VARCHAR(190) NOT NULL,
  telefon VARCHAR(30) NOT NULL,
  adresse VARCHAR(200) NOT NULL,
  ort VARCHAR(100) NOT NULL,
  land VARCHAR(100) NOT NULL,
  beschaeftigung VARCHAR(50) NOT NULL,
  einkommen DECIMAL(12,2) NOT NULL,
  bestehende_verbindlichkeiten DECIMAL(12,2) NOT NULL DEFAULT 0,
  kreditart VARCHAR(50) NOT NULL,
  kreditbetrag DECIMAL(12,2) NOT NULL,
  laufzeit INT NOT NULL,
  verwendungszweck VARCHAR(300) NOT NULL DEFAULT '',
  sms_verification ENUM('ja','nein') NOT NULL DEFAULT 'nein',
  sms_verifie TINYINT(1) NOT NULL DEFAULT 0,
  datenschutz TINYINT(1) NOT NULL DEFAULT 0,
  agb TINYINT(1) NOT NULL DEFAULT 0,
  schufa_zustimmung TINYINT(1) NOT NULL DEFAULT 0,
  statut ENUM('Neu','Analyse','Akzeptiert','Abgelehnt') NOT NULL DEFAULT 'Neu',
  assigne_a INT UNSIGNED NULL,
  note_interne TEXT NULL,
  visiteur_ville VARCHAR(100) NOT NULL DEFAULT '',
  visiteur_region VARCHAR(100) NOT NULL DEFAULT '',
  visiteur_pays VARCHAR(100) NOT NULL DEFAULT '',
  visiteur_localisation_affichage VARCHAR(200) NOT NULL DEFAULT '',
  ip_adresse VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  email_client_envoye TINYINT(1) NOT NULL DEFAULT 0,
  email_conseiller_envoye TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_statut_date (statut, created_at),
  INDEX idx_email (email),
  CONSTRAINT fk_demande_admin FOREIGN KEY (assigne_a) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demande_statut_historique (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  demande_id INT UNSIGNED NOT NULL,
  statut VARCHAR(20) NOT NULL,
  date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  modifie_par INT UNSIGNED NULL,
  commentaire VARCHAR(500) NOT NULL DEFAULT '',
  INDEX idx_demande (demande_id),
  CONSTRAINT fk_hist_demande FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_hist_admin FOREIGN KEY (modifie_par) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS demande_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  demande_id INT UNSIGNED NOT NULL,
  texte TEXT NOT NULL,
  auteur_admin_id INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_demande (demande_id),
  CONSTRAINT fk_note_demande FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE CASCADE,
  CONSTRAINT fk_note_admin FOREIGN KEY (auteur_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Messages de contact
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL DEFAULT '—',
  email VARCHAR(190) NOT NULL,
  telefon VARCHAR(30) NOT NULL DEFAULT '',
  betreff VARCHAR(200) NOT NULL DEFAULT '—',
  nachricht TEXT NOT NULL,
  datenschutz_accepte TINYINT(1) NOT NULL DEFAULT 0,
  statut ENUM('neu','beantwortet','geschlossen') NOT NULL DEFAULT 'neu',
  visiteur_localisation_affichage VARCHAR(200) NOT NULL DEFAULT '',
  ip_adresse VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_statut_date (statut, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_reponses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contact_id INT UNSIGNED NOT NULL,
  texte TEXT NOT NULL,
  auteur_admin_id INT UNSIGNED NULL,
  auteur_nom VARCHAR(100) NOT NULL DEFAULT '',
  envoye_par_email TINYINT(1) NOT NULL DEFAULT 0,
  erreur_envoi VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_contact (contact_id),
  CONSTRAINT fk_reponse_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
  CONSTRAINT fk_reponse_admin FOREIGN KEY (auteur_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Chat en direct
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visiteur_id VARCHAR(100) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL DEFAULT '',
  visiteur_ville VARCHAR(100) NOT NULL DEFAULT '',
  visiteur_region VARCHAR(100) NOT NULL DEFAULT '',
  visiteur_pays VARCHAR(100) NOT NULL DEFAULT '',
  visiteur_localisation_affichage VARCHAR(200) NOT NULL DEFAULT '',
  statut ENUM('ouvert','ferme') NOT NULL DEFAULT 'ouvert',
  admin_assigne_id INT UNSIGNED NULL,
  dernier_message VARCHAR(200) NOT NULL DEFAULT '',
  dernier_message_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dernier_expediteur ENUM('visiteur','admin') NULL,
  non_lu_admin INT NOT NULL DEFAULT 0,
  non_lu_visiteur INT NOT NULL DEFAULT 0,
  page_origine VARCHAR(255) NOT NULL DEFAULT '',
  ip_adresse VARCHAR(64) NOT NULL DEFAULT '',
  user_agent VARCHAR(255) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_statut_date (statut, dernier_message_date),
  CONSTRAINT fk_conv_admin FOREIGN KEY (admin_assigne_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  expediteur ENUM('visiteur','admin') NOT NULL,
  auteur_admin_id INT UNSIGNED NULL,
  auteur_nom VARCHAR(100) NOT NULL DEFAULT '',
  auteur_avatar VARCHAR(255) NOT NULL DEFAULT '',
  texte TEXT NOT NULL,
  lu TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conv_date (conversation_id, created_at),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_admin FOREIGN KEY (auteur_admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Réglages globaux
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cle VARCHAR(50) NOT NULL UNIQUE DEFAULT 'global',
  chat_actif TINYINT(1) NOT NULL DEFAULT 1,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (cle, chat_actif)
  SELECT 'global', 1 WHERE NOT EXISTS (SELECT 1 FROM settings WHERE cle = 'global');

-- ------------------------------------------------------------
-- Anti-spam (remplace express-rate-limit)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(50) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_bucket_ip_date (bucket, ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
