CREATE DATABASE IF NOT EXISTS plateforme_tournois
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE plateforme_tournois;


-- ============================================
-- TABLE : role_utilisateur
-- ============================================

CREATE TABLE role_utilisateur (
    id INT NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    libelle VARCHAR(50) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_role_code (code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TABLE : statut_inscription
-- ============================================

CREATE TABLE statut_inscription (
    id INT NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    libelle VARCHAR(50) NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_statut_inscription_code (code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TABLE : statut_tournoi
-- ============================================

CREATE TABLE statut_tournoi (
    id INT NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    libelle VARCHAR(50) NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_statut_tournoi_code (code)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TABLE : categorie
-- ============================================

CREATE TABLE categorie (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    categorie_parent_id INT DEFAULT NULL,
    description TEXT DEFAULT NULL,

    PRIMARY KEY (id),

    KEY fk_categorie_parent (categorie_parent_id),

    CONSTRAINT fk_categorie_parent
        FOREIGN KEY (categorie_parent_id)
        REFERENCES categorie (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TABLE : utilisateur
-- ============================================

CREATE TABLE utilisateur (
    id INT NOT NULL AUTO_INCREMENT,
    nom_utilisateur VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    prenom VARCHAR(50) DEFAULT NULL,
    nom VARCHAR(50) DEFAULT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    est_actif BOOLEAN NOT NULL DEFAULT TRUE,
    role_id INT NOT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_utilisateur_nom (nom_utilisateur),
    UNIQUE KEY uq_utilisateur_email (email),

    KEY fk_utilisateur_role (role_id),

    CONSTRAINT fk_utilisateur_role
        FOREIGN KEY (role_id)
        REFERENCES role_utilisateur (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TABLE : tournoi
-- ============================================

CREATE TABLE tournoi (
    id INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    regles TEXT DEFAULT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME DEFAULT NULL,
    date_limite_inscription DATETIME DEFAULT NULL,
    nombre_max_participants INT DEFAULT NULL,
    organisateur_id INT NOT NULL,
    categorie_id INT NOT NULL,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    statut_id INT NOT NULL,

    PRIMARY KEY (id),

    KEY fk_tournoi_organisateur (organisateur_id),
    KEY fk_tournoi_categorie (categorie_id),
    KEY fk_tournoi_statut (statut_id),

    CONSTRAINT fk_tournoi_organisateur
        FOREIGN KEY (organisateur_id)
        REFERENCES utilisateur (id),

    CONSTRAINT fk_tournoi_categorie
        FOREIGN KEY (categorie_id)
        REFERENCES categorie (id),

    CONSTRAINT fk_tournoi_statut
        FOREIGN KEY (statut_id)
        REFERENCES statut_tournoi (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================
-- TABLE : inscription
-- ============================================

CREATE TABLE inscription (
    id INT NOT NULL AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    tournoi_id INT NOT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut_id INT NOT NULL,

    PRIMARY KEY (id),

    UNIQUE KEY uq_inscription_utilisateur_tournoi
        (utilisateur_id, tournoi_id),

    KEY fk_inscription_tournoi (tournoi_id),
    KEY fk_inscription_statut (statut_id),

    CONSTRAINT fk_inscription_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inscription_tournoi
        FOREIGN KEY (tournoi_id)
        REFERENCES tournoi (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inscription_statut
        FOREIGN KEY (statut_id)
        REFERENCES statut_inscription (id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;