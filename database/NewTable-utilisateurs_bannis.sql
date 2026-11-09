CREATE TABLE utilisateurs_bannis (
    utilisateur_id INT NOT NULL,
    tournoi_id INT NOT NULL,

    PRIMARY KEY (utilisateur_id, tournoi_id),

    CONSTRAINT fk_bannis_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_bannis_tournoi
        FOREIGN KEY (tournoi_id)
        REFERENCES tournoi(id)
        ON DELETE CASCADE
);