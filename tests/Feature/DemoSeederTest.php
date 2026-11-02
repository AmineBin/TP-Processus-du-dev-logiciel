<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aucune de ces vérifications ne doit écrire dans la base configurée.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
    }

    public function test_complete_demo_dataset_populates_every_business_table(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertDatabaseCount('role_utilisateur', 2);
        $this->assertDatabaseCount('statut_inscription', 3);
        $this->assertDatabaseCount('statut_tournoi', 3);
        $this->assertDatabaseCount('categorie', 13);
        $this->assertDatabaseCount('utilisateur', 11);
        $this->assertDatabaseCount('tournoi', 15);
        $this->assertDatabaseCount('inscription', 20);
        $this->assertSame(3, DB::table('categorie')->whereNull('categorie_parent_id')->count());
        $this->assertTrue(Hash::check(
            'DemoTournois2026!',
            User::where('email', 'demo.visiteur@example.test')->firstOrFail()->mot_de_passe_hash,
        ));

        foreach (DB::table('utilisateur')->get() as $user) {
            $this->assertSame(10, strlen($user->numero_telephone));
        }
    }

    public function test_reseeding_preserves_accounts_ids_and_personal_tournaments(): void
    {
        $this->seed(DemoSeeder::class);
        $tables = ['role_utilisateur', 'statut_inscription', 'statut_tournoi', 'categorie', 'utilisateur', 'tournoi', 'inscription'];
        $ids = [];

        foreach ($tables as $table) {
            $ids[$table] = DB::table($table)->orderBy('id')->pluck('id')->all();
        }

        $passwordHash = Hash::make('my-existing-password');
        $adminRole = DB::table('role_utilisateur')->where('code', 'ADMIN')->value('id');
        DB::table('utilisateur')->whereIn('email', ['test@example.com', 'demo.visiteur@example.test'])->update([
            'mot_de_passe_hash' => $passwordHash,
            'est_actif' => false,
            'role_id' => $adminRole,
            'numero_telephone' => '0699999999',
        ]);
        $personal = (array) DB::table('tournoi')->first();
        unset($personal['id']);
        $personal['nom'] = 'Mon tournoi personnel à conserver';
        $personal['description'] = 'Ne pas écraser cette description.';
        $personalId = DB::table('tournoi')->insertGetId($personal);

        $this->seed(DemoSeeder::class);

        foreach ($tables as $table) {
            $query = DB::table($table)->orderBy('id');

            if ($table === 'tournoi') {
                $query->where('id', '!=', $personalId);
            }

            $this->assertSame($ids[$table], $query->pluck('id')->all(), $table);
        }

        foreach (['test@example.com', 'demo.visiteur@example.test'] as $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->assertSame($passwordHash, $user->mot_de_passe_hash);
            $this->assertFalse($user->est_actif);
            $this->assertSame($adminRole, $user->role_id);
            $this->assertSame('0699999999', $user->numero_telephone);
        }

        $this->assertDatabaseHas('tournoi', ['id' => $personalId, ...$personal]);
    }

    public function test_demo_uses_real_role_and_status_ids(): void
    {
        DB::table('role_utilisateur')->insert([
            ['id' => 41, 'code' => 'ADMIN', 'libelle' => 'Administrateur'],
            ['id' => 42, 'code' => 'UTILISATEUR', 'libelle' => 'Utilisateur'],
        ]);
        DB::table('statut_inscription')->insert([
            ['id' => 51, 'code' => 'EN_ATTENTE', 'libelle' => 'En attente'],
            ['id' => 52, 'code' => 'CONFIRMEE', 'libelle' => 'Confirmée'],
        ]);
        DB::table('statut_tournoi')->insert([
            ['id' => 71, 'code' => 'A_VENIR', 'libelle' => 'À venir'],
            ['id' => 72, 'code' => 'EN_COURS', 'libelle' => 'En cours'],
            ['id' => 73, 'code' => 'TERMINE', 'libelle' => 'Terminé'],
        ]);

        $this->seed(DemoSeeder::class);

        $this->assertDatabaseHas('utilisateur', ['email' => 'demo.visiteur@example.test', 'role_id' => 42]);
        $this->assertSame(20, DB::table('inscription')->where('statut_id', 52)->count());
        $this->assertSame(12, DB::table('tournoi')->where('statut_id', 71)->count());
        $this->assertSame(1, DB::table('tournoi')->where('statut_id', 72)->count());
        $this->assertSame(2, DB::table('tournoi')->where('statut_id', 73)->count());
    }

    public function test_demo_frontend_shows_all_categories_and_the_empty_state(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::where('email', 'demo.visiteur@example.test')->firstOrFail());

        $this->get('/home')->assertOk()->assertSee('Jeux vidéo')->assertSee('Sports')->assertSee('Jeux de table');
        $pages = [
            'fps' => 'Démo — Nuit Valorant',
            'combat' => 'Démo — Coupe Street Fighter',
            'speedrun' => 'Démo — Défi Speedrun Mario',
            'football' => 'Démo — Coupe de football du campus',
            'basketball' => 'Démo — Open basket 3 contre 3',
            'tennis' => 'Démo — Open tennis inscriptions closes',
            'natation' => 'Démo — Relais natation',
            'echecs' => 'Démo — Open blitz échecs',
            'poker' => 'Démo — Soirée poker découverte',
        ];

        foreach ($pages as $slug => $title) {
            $this->get('/sport/'.$slug)->assertOk()->assertSee($title);
        }

        $this->get('/sport/jeux-de-societe')->assertOk()->assertSee('Aucun tournoi');
    }

    public function test_demo_registration_scenarios_match_the_actual_controllers(): void
    {
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::where('email', 'demo.visiteur@example.test')->firstOrFail());

        $scenarios = [
            'Démo — Football découverte complet' => 'Désolé, ce tournoi est complet.',
            'Démo — Open tennis inscriptions closes' => 'La date limite d’inscription est dépassée.',
            'Démo — Open blitz échecs' => 'Tu es déjà inscrit à ce tournoi.',
        ];

        foreach ($scenarios as $name => $message) {
            $id = DB::table('tournoi')->where('nom', $name)->value('id');
            $url = '/tournoi/'.$id.'/inscription';
            $this->get($url)->assertOk();
            $this->post($url)->assertRedirect($url)->assertSessionHas('error', $message);
        }

        $this->assertDatabaseCount('inscription', 20);
        $openId = DB::table('tournoi')->where('nom', 'Tournoi FPS de démonstration')->value('id');
        $this->post('/tournoi/'.$openId.'/inscription')->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('inscription', 21);
    }

    public function test_demo_seeder_refuses_production_before_writing_data(): void
    {
        $this->app['env'] = 'production';
        $this->expectException(LogicException::class);

        try {
            $this->seed(DemoSeeder::class);
        } finally {
            $this->assertDatabaseCount('role_utilisateur', 0);
            $this->assertDatabaseCount('utilisateur', 0);
            $this->assertDatabaseCount('tournoi', 0);
        }
    }
}
