<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/** Initialise uniquement les rôles, sans comptes de démonstration ni suppression. */
class RoleSeeder extends Seeder
{
    /** Ajoute ou actualise les rôles par code en conservant les IDs existants. */
    public function run(): void
    {
        Role::query()->updateOrCreate(['code' => Role::USER_CODE], [
            'libelle' => 'Utilisateur',
            'description' => 'Utilisateur standard de la plateforme',
        ]);
        Role::query()->updateOrCreate(['code' => Role::ADMIN_CODE], [
            'libelle' => 'Administrateur',
            'description' => 'Administrateur de la plateforme',
        ]);
    }
}
