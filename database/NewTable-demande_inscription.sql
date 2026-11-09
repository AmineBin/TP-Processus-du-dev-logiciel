CREATE TABLE demande_inscription (
    id INT AUTO_INCREMENT PRIMARY KEY,

    nom_utilisateur VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    mot_de_passe_hash VARCHAR(255) NOT NULL,

    prenom VARCHAR(50),
    nom VARCHAR(50),

    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    date_naissance DATE,
    numero_telephone VARCHAR(20),

    role_id INT NOT NULL,

    statut ENUM('EN_ATTENTE', 'APPROUVEE', 'REFUSEE')
        NOT NULL DEFAULT 'EN_ATTENTE',

    date_traitement DATETIME NULL,

    traite_par INT NULL,

    motif_refus VARCHAR(500) NULL,

    CONSTRAINT fk_demande_role
        FOREIGN KEY (role_id)
        REFERENCES role_utilisateur(id),

    CONSTRAINT fk_demande_traite_par
        FOREIGN KEY (traite_par)
        REFERENCES utilisateur(id)
        ON DELETE SET NULL
);