USE plateforme_tournois;


-- ============================================
-- DONNÉES DE RÉFÉRENCE
-- ============================================

-- Rôles utilisateurs
INSERT INTO role_utilisateur (code, libelle, description)
VALUES
    ('UTILISATEUR', 'Utilisateur', 'Utilisateur standard de la plateforme'),
    ('ORGANISATEUR', 'Organisateur', 'Peut créer et gérer des tournois'),
    ('ADMIN', 'Administrateur', 'Administrateur de la plateforme');


-- Statuts des inscriptions
INSERT INTO statut_inscription (code, libelle)
VALUES
    ('EN_ATTENTE', 'En attente'),
    ('CONFIRMEE', 'Confirmée'),
    ('REFUSEE', 'Refusée'),
    ('ANNULEE', 'Annulée');


-- Statuts des tournois
INSERT INTO statut_tournoi (code, libelle)
VALUES
    ('BROUILLON', 'Brouillon'),
    ('OUVERT', 'Ouvert aux inscriptions'),
    ('EN_COURS', 'En cours'),
    ('TERMINE', 'Terminé'),
    ('ANNULE', 'Annulé');


-- ============================================
-- CATÉGORIES
-- ============================================

-- Catégories principales
INSERT INTO categorie (nom, categorie_parent_id, description)
VALUES
    ('Jeux vidéo', NULL, 'Tournois de jeux vidéo'),
    ('Sports', NULL, 'Tournois sportifs'),
    ('Jeux de cartes', NULL, 'Tournois de jeux de cartes');


-- Sous-catégories
INSERT INTO categorie (nom, categorie_parent_id, description)
VALUES
    ('FIFA', 1, 'Tournois FIFA'),
    ('NBA', 1, 'Tournois NBA'),
    ('Football', 2, 'Tournois de football'),
    ('Basketball', 2, 'Tournois de basketball'),
    ('Tennis', 2, 'Tournois de tennis');


-- ============================================
-- UTILISATEURS
-- ============================================

-- Les HASH_TEST_* sont uniquement des valeurs fictives
-- destinées aux données de démonstration.

INSERT INTO utilisateur
    (nom_utilisateur, email, numero_telephone, date_naissance, mot_de_passe_hash,
     prenom, nom, date_creation, est_actif, role_id)
VALUES
    ('admin', 'admin@tournois.fr', '+33600000001', '1985-04-12',
     'HASH_TEST_ADMIN', 'Admin', 'Plateforme', '2026-10-12 13:14:18', TRUE, 3),

    ('elie', 'elie@example.com', '+33600000002', '1995-07-19',
     'HASH_TEST_ELIE', 'Elie', 'Geagea', '2026-10-12 13:14:18', TRUE, 1),

    ('joueur1', 'joueur1@example.com', '+33600000003', '1990-05-14',
     'HASH_TEST_1', 'Jean', 'Dupont', '2026-10-12 13:14:18', TRUE, 1),

    ('joueur2', 'joueur2@example.com', '+33600000004', '2000-02-08',
     'HASH_TEST_2', 'Marie', 'Martin', '2026-10-12 13:14:18', TRUE, 1);


-- ============================================
-- TOURNOIS
-- ============================================

INSERT INTO tournoi
    (nom, description, regles,
     date_debut, date_fin, date_limite_inscription,
     nombre_max_participants,
     organisateur_id, categorie_id,
     date_creation, date_modification, statut_id)
VALUES
    (
        'Avignon FIFA Cup',
        'Tournoi FIFA organisé en ligne.',
        'Matchs en élimination directe.',
        '2026-11-15 18:00:00',
        '2026-11-15 22:00:00',
        '2026-11-10 23:59:00',
        32,
        1,
        4,
        '2026-10-12 13:16:11',
        '2026-10-12 15:10:34',
        2
    ),

    (
        'Tournoi de Tennis Avignon',
        'Tournoi de tennis amateur.',
        'Matchs en élimination directe.',
        '2026-12-05 09:00:00',
        '2026-12-06 18:00:00',
        '2026-11-30 23:59:00',
        16,
        1,
        8,
        '2026-10-12 13:16:11',
        '2026-10-12 14:39:36',
        1
    );


-- ============================================
-- INSCRIPTIONS
-- ============================================

INSERT INTO inscription
    (utilisateur_id, tournoi_id, date_inscription, statut_id)
VALUES
    (2, 1, '2026-10-12 13:17:44', 2),
    (3, 1, '2026-10-12 13:17:44', 2),
    (4, 1, '2026-10-12 13:17:44', 1),
    (2, 2, '2026-10-12 13:17:44', 2);