
<?php

require_once __DIR__ . '/../Models/InscriptionModel.php';

// Initialisation du modèle
$inscriptionModel = new InscriptionModel();

// Initialisation des variables utilisées par la vue
$erreurs = [];
$inscriptionValidee = false;

$donnees = [
    'equipe' => '',
    'capitaine' => '',
    'email' => '',
    'telephone' => '',
    'joueur1' => '',
    'joueur2' => '',
    'joueur3' => '',
];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupération des données envoyées
    foreach ($donnees as $champ => $valeur) {
        $donnees[$champ] = trim($_POST[$champ] ?? '');
    }

    // Vérification des informations
    $erreurs = $inscriptionModel->verifierInscription($donnees);

    // Si aucune erreur n'est détectée
    if (empty($erreurs)) {
        $inscriptionValidee = true;
    }
}

// Affichage de la page d'inscription
require __DIR__ . '/../Vue/inscription-basket.php';
