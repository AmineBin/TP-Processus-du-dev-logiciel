<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = [
                [
                    'code' => 'UTILISATEUR',
                    'libelle' => 'Utilisateur',
                    'description' => 'Utilisateur standard de la plateforme',
                ],
                [
                    'code' => 'ADMIN',
                    'libelle' => 'Administrateur',
                    'description' => 'Administrateur de la plateforme',
                ],
            ];

            foreach ($roles as $role) {
                DB::table('role_utilisateur')->updateOrInsert(
                    ['code' => $role['code']],
                    $role
                );
            }

            $inscriptionStatuses = [
                ['code' => 'EN_ATTENTE', 'libelle' => 'En attente'],
                ['code' => 'CONFIRMEE', 'libelle' => 'Confirmée'],
                ['code' => 'ANNULEE', 'libelle' => 'Annulée'],
            ];

            foreach ($inscriptionStatuses as $status) {
                DB::table('statut_inscription')->updateOrInsert(
                    ['code' => $status['code']],
                    $status
                );
            }

            $tournamentStatuses = [
                ['code' => 'A_VENIR', 'libelle' => 'À venir'],
                ['code' => 'EN_COURS', 'libelle' => 'En cours'],
                ['code' => 'TERMINE', 'libelle' => 'Terminé'],
            ];

            foreach ($tournamentStatuses as $status) {
                DB::table('statut_tournoi')->updateOrInsert(
                    ['code' => $status['code']],
                    $status
                );
            }

            DB::table('categorie')->updateOrInsert(
                ['nom' => 'Jeux vidéo'],
                ['description' => 'Tournois de jeux vidéo']
            );
            DB::table('categorie')->updateOrInsert(
                ['nom' => 'Sports'],
                ['description' => 'Tournois sportifs']
            );

            $videoGames = DB::table('categorie')->where('nom', 'Jeux vidéo')->first();
            $sports = DB::table('categorie')->where('nom', 'Sports')->first();

            DB::table('categorie')->updateOrInsert(
                ['nom' => 'FPS'],
                [
                    'categorie_parent_id' => $videoGames->id,
                    'description' => 'Jeux de tir à la première personne',
                ]
            );
            DB::table('categorie')->updateOrInsert(
                ['nom' => 'Football'],
                [
                    'categorie_parent_id' => $sports->id,
                    'description' => 'Tournois de football',
                ]
            );

            $userRoleId = DB::table('role_utilisateur')
                ->where('code', 'UTILISATEUR')
                ->value('id');
            $adminRoleId = DB::table('role_utilisateur')
                ->where('code', 'ADMIN')
                ->value('id');

            DB::table('utilisateur')->updateOrInsert(
                ['email' => 'test@example.com'],
                [
                    'nom_utilisateur' => 'testeur',
                    'mot_de_passe_hash' => Hash::make('password'),
                    'date_naissance' => '1995-05-15',
                    'numero_telephone' => '0612345678',
                    'prenom' => 'Test',
                    'nom' => 'Utilisateur',
                    'est_actif' => true,
                    'role_id' => $userRoleId,
                ]
            );

            DB::table('utilisateur')->updateOrInsert(
                ['email' => 'admin@example.com'],
                [
                    'nom_utilisateur' => 'admin',
                    'mot_de_passe_hash' => Hash::make('password'),
                    'date_naissance' => '1990-01-01',
                    'numero_telephone' => '0698765432',
                    'prenom' => 'Admin',
                    'nom' => 'Plateforme',
                    'est_actif' => true,
                    'role_id' => $adminRoleId,
                ]
            );

            $organizerId = DB::table('utilisateur')
                ->where('email', 'admin@example.com')
                ->value('id');
            $upcomingStatusId = DB::table('statut_tournoi')
                ->where('code', 'A_VENIR')
                ->value('id');
            $fpsCategoryId = DB::table('categorie')
                ->where('nom', 'FPS')
                ->value('id');

            DB::table('tournoi')->updateOrInsert(
                ['nom' => 'Tournoi FPS de démonstration'],
                [
                    'description' => 'Tournoi créé avec les données de démonstration.',
                    'regles' => 'Respect et fair-play entre les participants.',
                    'date_debut' => now()->addDays(14),
                    'date_fin' => now()->addDays(14)->addHours(4),
                    'date_limite_inscription' => now()->addDays(7),
                    'nombre_max_participants' => 32,
                    'organisateur_id' => $organizerId,
                    'categorie_id' => $fpsCategoryId,
                    'statut_id' => $upcomingStatusId,
                ]
            );
        });
    }
}
