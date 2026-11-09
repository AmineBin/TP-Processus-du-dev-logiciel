<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Tournament;
use App\Models\User;
use App\Support\ErrorHandler;
use Database\Seeders\DatabaseSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TournamentCreationTest extends TestCase
{
    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        // Ces tests ne doivent jamais utiliser la base MySQL configurée.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        $this->seed(DatabaseSeeder::class);

        $this->organizer = new User([
            'nom_utilisateur' => 'orga_test',
            'email' => 'orga-test@example.test',
            'mot_de_passe_hash' => Hash::make('password'),
            'date_naissance' => '1990-01-01',
            'numero_telephone' => '0600000000',
        ]);
        $this->organizer->role()->associate(Role::where('code', Role::ORGANIZER_CODE)->firstOrFail());
        $this->organizer->save();
        $this->organizer->refresh();
    }

    public function test_guests_cannot_open_or_submit_the_creation_form(): void
    {
        $this->get('/tournament')->assertRedirect('/login');
        $this->post('/tournament', $this->payload())->assertRedirect('/login');
        $this->postJson('/tournament', $this->payload())->assertUnauthorized()
            ->assertJsonPath('code', ErrorHandler::AUTH_REQUIRED);
        $this->assertDatabaseCount('tournoi', 1);
    }

    public function test_players_and_admins_cannot_create_even_with_a_valid_payload(): void
    {
        foreach (['test@example.com', 'admin@example.com'] as $email) {
            $this->actingAs(User::where('email', $email)->firstOrFail());
            $this->get('/tournament')->assertRedirect('/home')
                ->assertSessionHas('error_code', ErrorHandler::TOURNAMENT_FORBIDDEN);
            $this->postJson('/tournament', $this->payload())->assertForbidden()
                ->assertJsonPath('code', ErrorHandler::TOURNAMENT_FORBIDDEN);
            $this->postJson('/tournament', [])->assertForbidden();
        }

        $this->assertDatabaseCount('tournoi', 1);
    }

    public function test_disabled_organizers_are_logged_out_on_both_creation_routes(): void
    {
        $this->organizer->est_actif = false;
        $this->organizer->save();

        $this->actingAs($this->organizer)->getJson('/tournament')->assertForbidden()
            ->assertJsonPath('code', ErrorHandler::AUTH_INACTIVE);
        $this->assertGuest();
        $this->actingAs($this->organizer)->postJson('/tournament', $this->payload())->assertForbidden()
            ->assertJsonPath('code', ErrorHandler::AUTH_INACTIVE);
        $this->assertGuest();
        $this->assertDatabaseCount('tournoi', 1);
    }

    public function test_the_organizer_link_is_visible_only_to_organizers(): void
    {
        $this->actingAs($this->organizer)->get('/home')->assertOk()
            ->assertSee(route('tournaments.create'), false)->assertSee('Créer un tournoi');
        $this->actingAs(User::where('email', 'test@example.com')->firstOrFail())->get('/home')
            ->assertOk()->assertDontSee('Créer un tournoi');
    }

    public function test_form_lists_only_leaf_disciplines_and_uses_named_routes(): void
    {
        $fpsId = DB::table('categorie')->where('nom', 'FPS')->value('id');
        $nestedId = DB::table('categorie')->insertGetId([
            'nom' => 'Arena FPS', 'categorie_parent_id' => $fpsId,
        ]);

        $this->actingAs($this->organizer)->get('/tournament')->assertOk()
            ->assertSee(route('tournaments.store'), false)
            ->assertSee('css/tournament.css')
            ->assertViewHas('categories', function ($categories) use ($fpsId, $nestedId): bool {
                return ! $categories->contains('id', $fpsId)
                    && $categories->contains('id', $nestedId)
                    && $categories->every(fn ($category) => $category->categorie_parent_id !== null);
            });
    }

    public function test_successful_creation_is_visible_and_accepts_player_registration(): void
    {
        $this->actingAs($this->organizer)->post('/tournament', $this->payload())
            ->assertRedirect('/sport/fps')->assertSessionHas('success');
        $tournament = Tournament::where('nom', 'Coupe du campus')->firstOrFail();
        $this->assertSame($this->organizer->id, $tournament->organisateur_id);
        $this->assertSame(16, $tournament->nombre_max_participants);
        $this->get('/sport/fps')->assertOk()->assertSee('Coupe du campus');

        $player = User::where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($player)->get(route('tournament.registration', ['id' => $tournament->id]))
            ->assertOk()->assertSee('Confirmer mon inscription')
            ->assertSee(route('tournament.registration.store', ['id' => $tournament->id]), false);
        $this->post('/tournoi/'.$tournament->id.'/inscription')
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('inscription', [
            'tournoi_id' => $tournament->id, 'utilisateur_id' => $player->id,
        ]);
    }

    public function test_json_creation_imposes_the_authenticated_organizer(): void
    {
        $playerId = User::where('email', 'test@example.com')->value('id');
        $response = $this->actingAs($this->organizer)->postJson('/tournament', [
            ...$this->payload(),
            'organisateur_id' => $playerId,
            'id' => 9999,
            'password' => 'must-not-be-stored',
        ])->assertCreated()->assertJsonPath('message', 'Tournoi créé avec succès.')
            ->assertJsonPath('redirect_url', route('sport', ['name' => 'fps']));

        $id = $response->json('data.id');
        $this->assertNotSame(9999, $id);
        $this->assertDatabaseHas('tournoi', ['id' => $id, 'organisateur_id' => $this->organizer->id]);
        $this->assertDatabaseCount('tournoi', 2);
    }

    public function test_organizer_authorization_uses_the_role_code_not_its_id(): void
    {
        Role::where('code', Role::ORGANIZER_CODE)->update(['code' => 'LEGACY_ORGANIZER']);
        $role = new Role(['code' => Role::ORGANIZER_CODE, 'libelle' => 'Organisateur']);
        $role->id = 42;
        $role->save();
        $this->organizer->role()->associate($role);
        $this->organizer->save();

        $this->actingAs($this->organizer)->postJson('/tournament', $this->payload())->assertCreated();
    }

    public function test_removing_the_organizer_role_revokes_creation_access(): void
    {
        $this->organizer->role()->associate(Role::where('code', Role::USER_CODE)->firstOrFail());
        $this->organizer->save();
        $this->actingAs($this->organizer)->postJson('/tournament', $this->payload())->assertForbidden()
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_FORBIDDEN);
        $this->assertDatabaseCount('tournoi', 1);
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_do_not_create_a_tournament(string $field, mixed $value): void
    {
        $this->actingAs($this->organizer)->postJson('/tournament', [
            ...$this->payload(), $field => $value,
        ])->assertUnprocessable()->assertJsonPath('code', ErrorHandler::VALIDATION_FAILED)
            ->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('tournoi', 1);
    }

    /** @return array<string, array{string, mixed}> Limites et types de l’entrée HTTP. */
    public static function invalidFields(): array
    {
        return [
            'nom absent' => ['nom', null],
            'nom trop long' => ['nom', str_repeat('a', 101)],
            'description trop longue' => ['description', str_repeat('a', 5001)],
            'règles trop longues' => ['regles', str_repeat('a', 5001)],
            'début absent' => ['date_debut', null],
            'début invalide' => ['date_debut', 'not-a-date'],
            'fin absente' => ['date_fin', null],
            'clôture absente' => ['date_limite_inscription', null],
            'capacité absente' => ['nombre_max_participants', null],
            'capacité zéro' => ['nombre_max_participants', 0],
            'capacité négative' => ['nombre_max_participants', -2],
            'capacité décimale' => ['nombre_max_participants', 1.5],
            'capacité texte' => ['nombre_max_participants', 'beaucoup'],
            'capacité hors plage SQL' => ['nombre_max_participants', '2147483648'],
            'catégorie absente' => ['categorie_id', null],
            'catégorie inexistante' => ['categorie_id', 9999],
            'catégorie de mauvais type' => ['categorie_id', ['invalid']],
            'statut absent' => ['statut_id', null],
            'statut inexistant' => ['statut_id', 9999],
        ];
    }

    public function test_roots_and_intermediate_categories_are_rejected_server_side(): void
    {
        $rootId = DB::table('categorie')->whereNull('categorie_parent_id')->value('id');
        $fpsId = DB::table('categorie')->where('nom', 'FPS')->value('id');
        DB::table('categorie')->insert(['nom' => 'Sous-discipline FPS', 'categorie_parent_id' => $fpsId]);
        $this->actingAs($this->organizer);

        foreach ([$rootId, $fpsId] as $id) {
            $this->postJson('/tournament', [...$this->payload(), 'categorie_id' => $id])
                ->assertUnprocessable()->assertJsonValidationErrors('categorie_id');
        }

        $this->assertDatabaseCount('tournoi', 1);
    }

    public function test_dates_must_be_coherent(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->organizer)->postJson('/tournament', [
            ...$payload,
            'date_fin' => $payload['date_debut'],
            'date_limite_inscription' => now()->addDays(8)->format('Y-m-d\TH:i'),
        ])->assertUnprocessable()->assertJsonValidationErrors(['date_fin', 'date_limite_inscription']);
        $this->assertDatabaseCount('tournoi', 1);
    }

    public function test_validation_preserves_all_form_values_without_injected_fields(): void
    {
        $payload = $this->payload();
        $this->actingAs($this->organizer)->post('/tournament', [
            ...$payload,
            'date_fin' => now()->subDay()->format('Y-m-d\TH:i'),
            'organisateur_id' => 9999,
            'password' => 'must-not-be-flashed',
        ])->assertRedirect('/tournament')->assertSessionHasErrors('date_fin')
            ->assertSessionMissing('_old_input.organisateur_id')->assertSessionMissing('_old_input.password');

        $response = $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/tournament')->assertOk();
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        foreach (['nom', 'date_debut', 'date_limite_inscription', 'nombre_max_participants'] as $field) {
            $this->assertSame((string) $payload[$field], $xpath->query('//input[@name="'.$field.'"]')->item(0)->getAttribute('value'));
        }

        foreach (['description', 'regles'] as $field) {
            $this->assertSame($payload[$field], $xpath->query('//textarea[@name="'.$field.'"]')->item(0)->textContent);
        }

        foreach (['categorie_id', 'statut_id'] as $field) {
            $this->assertSame((string) $payload[$field], $xpath->query('//select[@name="'.$field.'"]/option[@selected]')->item(0)->getAttribute('value'));
        }
    }

    public function test_database_errors_roll_back_the_insert_and_hide_sql_in_json(): void
    {
        config(['app.debug' => true]);
        Tournament::created(function (): void {
            throw new QueryException('sqlite', 'sensitive SQL', [], new \PDOException('private details'));
        });

        try {
            $this->actingAs($this->organizer)->postJson('/tournament', $this->payload())
                ->assertStatus(503)->assertExactJson([
                    'code' => ErrorHandler::TOURNAMENT_CREATION_FAILED,
                    'message' => ErrorHandler::message(ErrorHandler::TOURNAMENT_CREATION_FAILED),
                ]);
            $this->assertDatabaseCount('tournoi', 1);
        } finally {
            Tournament::flushEventListeners();
        }
    }

    public function test_database_errors_return_to_the_html_form_with_old_input(): void
    {
        Tournament::creating(function (): void {
            throw new QueryException('sqlite', 'sensitive SQL', [], new \PDOException('private details'));
        });

        try {
            $this->actingAs($this->organizer)->post('/tournament', $this->payload())
                ->assertRedirect('/tournament')->assertSessionHas('error_code', ErrorHandler::TOURNAMENT_CREATION_FAILED)
                ->assertSessionHasInput('nom', 'Coupe du campus');
        } finally {
            Tournament::flushEventListeners();
        }
    }

    public function test_the_form_handles_missing_reference_data_without_a_server_error(): void
    {
        DB::table('tournoi')->delete();
        DB::table('categorie')->delete();
        DB::table('statut_tournoi')->delete();
        $this->actingAs($this->organizer)->get('/tournament')->assertOk()
            ->assertSee('Les disciplines ou les statuts ne sont pas encore disponibles.')
            ->assertSee('type="submit" disabled', false);
    }

    private function payload(): array
    {
        return [
            'nom' => 'Coupe du campus',
            'description' => 'Rencontre ouverte aux joueurs du campus.',
            'regles' => 'Respect et fair-play.',
            'date_debut' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'date_fin' => now()->addDays(7)->addHours(4)->format('Y-m-d\TH:i'),
            'date_limite_inscription' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'nombre_max_participants' => 16,
            'categorie_id' => Category::where('nom', 'FPS')->value('id'),
            'statut_id' => DB::table('statut_tournoi')->where('code', 'A_VENIR')->value('id'),
        ];
    }
}
