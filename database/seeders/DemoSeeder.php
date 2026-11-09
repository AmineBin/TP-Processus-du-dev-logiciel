<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

/** Étend le jeu minimal avec des scénarios visuels, uniquement en local et en test. */
class DemoSeeder extends DatabaseSeeder
{
    /** Peuple les tables métier sans suppression ni réinitialisation des comptes. */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('DemoSeeder est réservé aux environnements local et testing.');
        }

        DB::transaction(function (): void {
            parent::run();

            $categories = $this->seedCategories();
            $users = $this->seedParticipants();
            // Ce compte à mot de passe public reste dans le seed limité au développement.
            $organizerId = $this->createUserIfMissing('organisateur@example.com', [
                'nom_utilisateur' => 'organisateur',
                'mot_de_passe_hash' => Hash::make('password'),
                'date_naissance' => '1992-03-20',
                'numero_telephone' => '0611223344',
                'prenom' => 'Orga',
                'nom' => 'Tournoi',
                'est_actif' => true,
                'role_id' => DB::table('role_utilisateur')->where('code', Role::ORGANIZER_CODE)->value('id'),
            ]);
            $tournaments = $this->seedTournaments($categories, $organizerId);
            $this->seedRegistrations($users, $tournaments);
        });
    }

    /** @return array<string, int> IDs des disciplines, après création des racines. */
    private function seedCategories(): array
    {
        $groups = [
            'Jeux vidéo' => [
                'description' => 'Compétitions sur PC et consoles, en solo ou en équipe.',
                'disciplines' => ['FPS', 'Combat', 'Speedrun'],
            ],
            'Sports' => [
                'description' => 'Rencontres sportives du campus, pour tous les niveaux.',
                'disciplines' => ['Football', 'Basketball', 'Tennis', 'Natation'],
            ],
            'Jeux de table' => [
                'description' => 'Stratégie, réflexion et rencontres autour d’une table.',
                'disciplines' => ['Échecs', 'Poker', 'Jeux de société'],
            ],
        ];
        $categories = [];

        foreach ($groups as $name => $group) {
            $parentId = $this->categoryId($name, null, $group['description']);

            foreach ($group['disciplines'] as $discipline) {
                $categories[$discipline] = $this->categoryId(
                    $discipline,
                    $parentId,
                    'Tournois et rencontres de '.$discipline.'.',
                );
            }
        }

        return $categories;
    }

    /** Retrouve une catégorie sans écraser les descriptions ou déplacer les existantes. */
    private function categoryId(string $name, ?int $parentId, string $description): int
    {
        $id = DB::table('categorie')->where('nom', $name)
            ->where('categorie_parent_id', $parentId)->value('id');

        return $id !== null ? (int) $id : DB::table('categorie')->insertGetId([
            'nom' => $name,
            'categorie_parent_id' => $parentId,
            'description' => $description,
        ]);
    }

    /** @return array<int, int> Compte de visite en position 0, puis huit participants. */
    private function seedParticipants(): array
    {
        $profiles = [
            ['Alex', 'Démo'],
            ['Camille', 'Dupont'],
            ['Léa', 'Martin'],
            ['Hugo', 'Bernard'],
            ['Inès', 'Petit'],
            ['Adam', 'Robert'],
            ['Chloé', 'Moreau'],
            ['Louis', 'Simon'],
            ['Sarah', 'Laurent'],
        ];
        $roleId = (int) DB::table('role_utilisateur')->where('code', Role::USER_CODE)->value('id');
        $users = [];

        foreach ($profiles as $index => [$firstName, $lastName]) {
            $identifier = $index === 0 ? 'visiteur' : 'joueur'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $users[$index] = $this->createUserIfMissing('demo.'.$identifier.'@example.test', [
                'nom_utilisateur' => 'demo_'.$identifier,
                'prenom' => $firstName,
                'nom' => $lastName,
                'mot_de_passe_hash' => Hash::make('DemoTournois2026!'),
                'date_naissance' => '2000-01-15',
                'numero_telephone' => '060000000'.$index,
                'role_id' => $roleId,
                'est_actif' => true,
            ]);
        }

        return $users;
    }

    /**
     * Les dates de ces fixtures sont recalculées à chaque exécution.
     * Les tournois préfixés « Démo — » sont actualisés par nom, sans doublons.
     *
     * @param  array<string, int>  $categories  IDs des disciplines.
     * @return array<string, int> IDs des tournois de chaque scénario.
     */
    private function seedTournaments(array $categories, int $organizerId): array
    {
        $statuses = DB::table('statut_tournoi')->pluck('id', 'code');
        $rules = [
            'FPS' => 'Équipes équilibrées, anti-triche obligatoire et fair-play. Les débutants sont bienvenus.',
            'Combat' => 'Matchs en deux manches gagnantes, double élimination. Manette personnelle autorisée.',
            'Speedrun' => 'Version identique pour tous, chronométrage officiel et aucune modification du jeu.',
            'Football' => 'Rencontres à cinq, chaussures adaptées et protège-tibias recommandés.',
            'Basketball' => 'Format 3 contre 3, matchs de dix minutes et rotation des équipes.',
            'Tennis' => 'Simple, matchs en un set et arbitrage partagé. Apporter sa raquette.',
            'Natation' => 'Savoir nager 100 mètres, bonnet obligatoire et respect des consignes de sécurité.',
            'Échecs' => 'Cadence 5 minutes plus 3 secondes par coup. Téléphones éteints pendant les parties.',
            'Poker' => 'Jetons fictifs uniquement, sans argent réel ni frais de participation.',
        ];
        $tournaments = [
            'fps_demo' => (int) DB::table('tournoi')->where('nom', 'Tournoi FPS de démonstration')->value('id'),
        ];

        DB::table('tournoi')->where('id', $tournaments['fps_demo'])->update(['organisateur_id' => $organizerId]);

        foreach ($this->tournamentFixtures() as $key => [$name, $category, $startDays, $deadlineDays, $capacity, $status, $description]) {
            $start = now()->startOfDay()->addDays($startDays)->setHour(18);

            if ($status === 'EN_COURS') {
                $start = now()->subHour();
            }

            DB::table('tournoi')->updateOrInsert(['nom' => $name], [
                'description' => $description,
                'regles' => $rules[$category],
                'date_debut' => $start,
                'date_fin' => $start->copy()->addHours(4),
                'date_limite_inscription' => now()->startOfDay()->addDays($deadlineDays)->setHour(12),
                'nombre_max_participants' => $capacity,
                'organisateur_id' => $organizerId,
                'categorie_id' => $categories[$category],
                'statut_id' => $statuses[$status],
            ]);
            $tournaments[$key] = (int) DB::table('tournoi')->where('nom', $name)->value('id');
        }

        return $tournaments;
    }

    /**
     * Chaque ligne décrit : nom, discipline, jours avant début, jours avant clôture,
     * capacité, code du statut et description visible sur les cartes du frontend.
     * « Jeux de société » reste volontairement sans tournoi.
     *
     * @return array<string, array{string, string, int, int, int, string, string}>
     */
    private function tournamentFixtures(): array
    {
        return [
            'valorant' => [
                'Démo — Nuit Valorant', 'FPS', 10, 7, 16, 'A_VENIR',
                'Une soirée tactique sur le campus : forme ton équipe et découvre une compétition accessible à tous.',
            ],
            'cs2' => [
                'Démo — Arena Counter-Strike', 'FPS', 0, -2, 8, 'EN_COURS',
                'La compétition est en cours. Les inscriptions sont clôturées et les équipes disputent les phases finales.',
            ],
            'combat' => [
                'Démo — Coupe Street Fighter', 'Combat', 12, 9, 16, 'A_VENIR',
                'Affronte les joueurs du campus dans une ambiance conviviale. Tous les niveaux sont acceptés.',
            ],
            'speedrun' => [
                'Démo — Défi Speedrun Mario', 'Speedrun', 18, 15, 24, 'A_VENIR',
                'Un seul objectif : terminer le parcours le plus vite possible. Des passages de découverte précèdent les essais officiels.',
            ],
            'football' => [
                'Démo — Coupe de football du campus', 'Football', 14, 10, 32, 'A_VENIR',
                'Rejoins les équipes étudiantes pour une après-midi de football à cinq. Quatre participants sont déjà inscrits.',
            ],
            'full' => [
                'Démo — Football découverte complet', 'Football', 7, 5, 8, 'A_VENIR',
                'Les huit places sont déjà occupées. Ce tournoi permet de tester le message de refus lorsque la capacité est atteinte.',
            ],
            'basket' => [
                'Démo — Open basket 3 contre 3', 'Basketball', 16, 12, 24, 'A_VENIR',
                'Des rencontres courtes sur le terrain extérieur, des équipes mixtes et une finale en fin de journée.',
            ],
            'tennis_closed' => [
                'Démo — Open tennis inscriptions closes', 'Tennis', 3, -1, 16, 'A_VENIR',
                'Le tournoi commence bientôt, mais la date limite est dépassée. Utilise ce scénario pour tester les inscriptions clôturées.',
            ],
            'swimming' => [
                'Démo — Relais natation', 'Natation', 21, 17, 12, 'A_VENIR',
                'Une rencontre en piscine autour du relais et du dépassement de soi, avec échauffement collectif et encadrement sur place.',
            ],
            'chess' => [
                'Démo — Open blitz échecs', 'Échecs', 9, 6, 32, 'A_VENIR',
                'Le compte de visite est déjà inscrit à ce tournoi : ouvre sa fiche pour voir le message de participation existante.',
            ],
            'chess_masters' => [
                'Démo — Masters échecs', 'Échecs', 2, 1, 8, 'A_VENIR',
                'Dernier jour pour rejoindre cette rencontre en petit comité. Huit places pour des parties rapides et intenses.',
            ],
            'poker' => [
                'Démo — Soirée poker découverte', 'Poker', 11, 8, 8, 'A_VENIR',
                'Découvre les règles du poker autour de jetons fictifs. Aucun argent réel : la soirée privilégie la stratégie et la convivialité.',
            ],
            'poker_finished' => [
                'Démo — Finale poker terminée', 'Poker', -7, -10, 8, 'TERMINE',
                'Exemple de tournoi passé : la finale s’est achevée la semaine dernière. Les inscriptions ne sont plus possibles.',
            ],
            'combat_finished' => [
                'Démo — Coupe rétro terminée', 'Combat', -14, -17, 16, 'TERMINE',
                'Une compétition rétro déjà terminée, conservée pour comparer l’affichage des événements passés et à venir.',
            ],
        ];
    }

    /**
     * Ajoute les inscriptions manquantes sans modifier celles déjà présentes.
     * Les clés étrangères sont résolues par code et par identifiant réel.
     *
     * @param  array<int, int>  $users  Compte de visite puis participants.
     * @param  array<string, int>  $tournaments  Tournois des scénarios.
     */
    private function seedRegistrations(array $users, array $tournaments): void
    {
        $confirmedId = DB::table('statut_inscription')->where('code', 'CONFIRMEE')->value('id');
        $scenarios = [
            'full' => range(1, 8),
            'valorant' => [1, 2, 3],
            'football' => [1, 2, 3, 4],
            'tennis_closed' => [5, 6],
            'fps_demo' => [7, 8],
            'chess' => [0],
        ];

        foreach ($scenarios as $key => $participants) {
            foreach ($participants as $participant) {
                DB::table('inscription')->insertOrIgnore([
                    'utilisateur_id' => $users[$participant],
                    'tournoi_id' => $tournaments[$key],
                    'date_inscription' => now()->subDay(),
                    'statut_id' => $confirmedId,
                ]);
            }
        }
    }
}
