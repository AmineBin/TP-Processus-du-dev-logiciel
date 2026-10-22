
<?php

class InscriptionModel
{
    public function verifierInscription(array $donnees): array
    {
        $erreurs = [];

        $champsObligatoires = [
            'equipe' => "Le nom de l'équipe",
            'capitaine' => 'Le nom du capitaine',
            'email' => "L'adresse e-mail",
            'telephone' => 'Le numéro de téléphone',
            'joueur1' => 'Le joueur 1',
            'joueur2' => 'Le joueur 2',
            'joueur3' => 'Le joueur 3',
        ];

        foreach ($champsObligatoires as $champ => $libelle) {
            if (trim($donnees[$champ] ?? '') === '') {
                $erreurs[] = "$libelle est obligatoire.";
            }
        }

        if (
            !empty($donnees['email'])
            && !filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)
        ) {
            $erreurs[] = "L'adresse e-mail est invalide.";
        }

        $joueurs = array_map(
            fn($nom) => mb_strtolower(trim($nom)),
            [
                $donnees['joueur1'] ?? '',
                $donnees['joueur2'] ?? '',
                $donnees['joueur3'] ?? '',
            ]
        );

        if (
            !in_array('', $joueurs, true)
            && count(array_unique($joueurs)) !== 3
        ) {
            $erreurs[] = 'Les trois joueurs doivent être différents.';
        }

        return $erreurs;
    }
}
