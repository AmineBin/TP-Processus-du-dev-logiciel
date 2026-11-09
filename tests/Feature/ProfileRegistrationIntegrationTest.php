<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RegistrationStatus;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\User;
use App\Support\ErrorHandler;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileRegistrationIntegrationTest extends TestCase
{
    private User $player;

    private User $other;

    private Tournament $tournament;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        $this->seed(DatabaseSeeder::class);
        $this->player = User::where('email', 'test@example.com')->firstOrFail();
        $this->other = User::where('email', 'admin@example.com')->firstOrFail();
        $this->tournament = Tournament::firstOrFail();
    }

    public function test_profile_is_protected_for_guests_and_disabled_accounts(): void
    {
        $this->get('/profil')->assertRedirect('/login');
        $this->player->est_actif = false;
        $this->player->save();
        $this->actingAs($this->player)->getJson('/profil')->assertForbidden()
            ->assertJsonPath('code', ErrorHandler::AUTH_INACTIVE);
        $this->assertGuest();
    }

    public function test_profile_shows_only_the_session_user_and_their_history(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-09 12:00:00'));
        $otherTournament = $this->createTournament('Tournoi privé du second compte');
        $this->registration($this->player, $this->tournament, RegistrationStatus::CONFIRMED);
        $this->registration($this->other, $otherTournament, RegistrationStatus::CONFIRMED);

        $this->actingAs($this->player)->get('/profil?user_id='.$this->other->id)->assertOk()
            ->assertSee($this->player->email)->assertSee('31 ans')->assertSee($this->tournament->nom)
            ->assertDontSee($this->other->email)->assertDontSee($otherTournament->nom)
            ->assertDontSee($this->player->mot_de_passe_hash, false)->assertSee('Se déconnecter');
        $this->get('/profil/'.$this->other->id)->assertNotFound();
    }

    public function test_profile_escapes_user_supplied_values(): void
    {
        $this->player->prenom = '<script>alert(1)</script>';
        $this->player->save();
        $this->actingAs($this->player)->get('/profil')->assertOk()
            ->assertSee($this->player->prenom)->assertDontSee($this->player->prenom, false);
    }

    public function test_profile_empty_state_and_logout_are_connected(): void
    {
        $this->actingAs($this->player)->get('/home')->assertOk()->assertSee(route('profil'), false);
        $this->get('/profil')->assertOk()->assertSee("Tu n'es inscrit(e) à aucun tournoi", false)
            ->assertSee('action="'.route('logout').'"', false);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->get('/profil')->assertRedirect('/login');
    }

    #[DataProvider('categoryNames')]
    public function test_slugs_from_home_resolve_the_actual_category_name(string $name): void
    {
        $parent = Category::where('nom', 'Sports')->firstOrFail();
        $id = DB::table('categorie')->insertGetId(['nom' => $name, 'categorie_parent_id' => $parent->id]);
        $tournament = $this->createTournament('Rencontre de '.$name, ['categorie_id' => $id]);
        $url = route('sport', ['name' => Str::slug($name)]);

        $this->actingAs($this->player)->get('/home')->assertOk()->assertSee($url, false);
        $this->get($url)->assertOk()->assertViewHas('name', $name)->assertSee($tournament->nom);
    }

    /** @return array<string, array{string}> Espaces, accents, majuscules et tirets. */
    public static function categoryNames(): array
    {
        return [
            'espaces' => ['Tennis de table'],
            'accents' => ['Échecs rapides'],
            'plusieurs mots' => ['Jeux de société'],
            'majuscules' => ['BADMINTON'],
            'apostrophe' => ['Course d’orientation'],
        ];
    }

    public function test_parent_categories_include_their_whole_subtree_and_direct_tournaments(): void
    {
        $root = Category::where('nom', 'Sports')->firstOrFail();
        $child = DB::table('categorie')->insertGetId(['nom' => 'Tennis de table', 'categorie_parent_id' => $root->id]);
        $grandchild = DB::table('categorie')->insertGetId(['nom' => 'Double mixte', 'categorie_parent_id' => $child]);
        $direct = $this->createTournament('Rencontre générale', ['categorie_id' => $root->id]);
        $nested = $this->createTournament('Rencontre double mixte', ['categorie_id' => $grandchild]);

        $this->actingAs($this->player)->get('/sport/sports')->assertOk()
            ->assertSee($direct->nom)->assertSee($nested->nom)->assertDontSee($this->tournament->nom);
        $this->get('/sport')->assertOk()->assertSee($direct->nom)->assertSee($nested->nom)->assertSee($this->tournament->nom);
        $this->get('/sport/categorie-inconnue')->assertNotFound();
    }

    #[DataProvider('registrationStates')]
    public function test_list_detail_and_profile_display_the_real_registration_status(string $code, string $label, bool $active): void
    {
        $this->registration($this->player, $this->tournament, $code);
        $this->actingAs($this->player)->get('/sport/fps')->assertOk()->assertSee($label)
            ->assertViewHas('mesInscriptions', fn ($items) => $items->count() === ($active ? 1 : 0));
        $this->get('/profil')->assertOk()->assertSee($label)->assertViewHas('inscriptionsActives', $active ? 1 : 0);
        $response = $this->get($this->registrationUrl())->assertOk()->assertSee($label);

        if ($active) {
            $response->assertDontSee('type="submit"', false);
        } else {
            $response->assertSee('Renouveler mon inscription');
            $this->get('/sport/fps')->assertSee('Se réinscrire au tournoi');
        }
    }

    /** @return array<string, array{string, string, bool}> Seules attente et confirmation occupent une place. */
    public static function registrationStates(): array
    {
        return [
            'attente' => [RegistrationStatus::PENDING, 'En attente', true],
            'confirmation' => [RegistrationStatus::CONFIRMED, 'Confirmée', true],
            'annulation' => [RegistrationStatus::CANCELLED, 'Annulée', false],
        ];
    }

    public function test_another_users_registration_does_not_mark_the_viewer_as_registered(): void
    {
        $this->registration($this->other, $this->tournament, RegistrationStatus::CONFIRMED);
        $this->actingAs($this->player)->get('/sport/fps')->assertOk()
            ->assertViewHas('inscriptions', fn ($items) => $items->isEmpty())
            ->assertViewHas('mesInscriptions', fn ($items) => $items->isEmpty())
            ->assertSee('S&#039;inscrire au tournoi', false);
        $this->get('/profil')->assertViewHas('inscriptions', fn ($items) => $items->isEmpty());
    }

    public function test_cancelled_registration_is_reactivated_in_place_as_pending(): void
    {
        $registration = $this->registration($this->player, $this->tournament, RegistrationStatus::CANCELLED);
        $id = $registration->id;
        $this->actingAs($this->player)->postJson($this->registrationUrl(), [
            'utilisateur_id' => $this->other->id,
            'statut_id' => RegistrationStatus::where('code', RegistrationStatus::CONFIRMED)->value('id'),
        ])->assertOk();

        $registration->refresh();
        $this->assertSame($id, $registration->id);
        $this->assertSame(RegistrationStatus::PENDING, $registration->status->code);
        $this->assertSame($this->player->id, $registration->utilisateur_id);
        $this->assertDatabaseCount('inscription', 1);
        $this->postJson($this->registrationUrl())->assertConflict()
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_ALREADY_REGISTERED);
        $this->assertDatabaseCount('inscription', 1);
    }

    public function test_cancelled_rows_free_places_but_cannot_reenroll_after_someone_takes_the_last_place(): void
    {
        $this->tournament->nombre_max_participants = 1;
        $this->tournament->save();
        $cancelled = $this->registration($this->other, $this->tournament, RegistrationStatus::CANCELLED);
        $this->actingAs($this->player)->postJson($this->registrationUrl())->assertOk();
        $this->assertDatabaseCount('inscription', 2);
        $this->assertSame(1, $this->tournament->registrations()->active()->count());

        $this->actingAs($this->other)->postJson($this->registrationUrl())->assertConflict()
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_FULL);
        $this->assertSame(RegistrationStatus::CANCELLED, $cancelled->fresh()->status->code);
        $this->get($this->registrationUrl())->assertOk()->assertSee('ce tournoi est complet')
            ->assertDontSee('Renouveler mon inscription');
    }

    #[DataProvider('activeRegistrationStates')]
    public function test_active_registration_states_reserve_capacity(string $code): void
    {
        $this->tournament->nombre_max_participants = 1;
        $this->tournament->save();
        $this->registration($this->other, $this->tournament, $code);
        $this->actingAs($this->player)->postJson($this->registrationUrl())->assertConflict()
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_FULL);
        $this->get($this->registrationUrl())->assertDontSee('Confirmer mon inscription');
        $this->assertDatabaseCount('inscription', 1);
    }

    /** @return array<string, array{string}> États qui réservent une place. */
    public static function activeRegistrationStates(): array
    {
        return ['attente' => [RegistrationStatus::PENDING], 'confirmation' => [RegistrationStatus::CONFIRMED]];
    }

    public function test_closed_tournaments_refuse_reactivation_without_changing_history(): void
    {
        $registration = $this->registration($this->player, $this->tournament, RegistrationStatus::CANCELLED);
        $this->tournament->date_limite_inscription = now()->subMinute();
        $this->tournament->save();
        $this->actingAs($this->player)->postJson($this->registrationUrl())->assertConflict()
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_REGISTRATION_CLOSED);
        $this->assertSame(RegistrationStatus::CANCELLED, $registration->fresh()->status->code);
        $this->get($this->registrationUrl())->assertSee('La date limite d’inscription est dépassée.')
            ->assertDontSee('Renouveler mon inscription');
    }

    public function test_missing_pending_status_returns_a_controlled_error(): void
    {
        RegistrationStatus::where('code', RegistrationStatus::PENDING)->delete();
        $this->actingAs($this->player)->postJson($this->registrationUrl())->assertStatus(503)
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_REGISTRATION_UNAVAILABLE);
        $this->assertDatabaseCount('inscription', 0);
    }

    public function test_unknown_existing_status_is_not_silently_reactivated(): void
    {
        $statusId = DB::table('statut_inscription')->insertGetId(['code' => 'REFUSEE', 'libelle' => 'Refusée']);
        DB::table('inscription')->insert([
            'utilisateur_id' => $this->player->id, 'tournoi_id' => $this->tournament->id, 'statut_id' => $statusId,
        ]);
        $this->actingAs($this->player)->postJson($this->registrationUrl())->assertStatus(503)
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_REGISTRATION_UNAVAILABLE);
        $this->assertDatabaseHas('inscription', ['statut_id' => $statusId]);
    }

    public function test_registration_status_is_resolved_by_code_even_when_its_id_is_not_one(): void
    {
        RegistrationStatus::where('code', RegistrationStatus::PENDING)->update(['id' => 42]);
        $this->actingAs($this->player)->postJson($this->registrationUrl())->assertOk();
        $this->assertDatabaseHas('inscription', [
            'utilisateur_id' => $this->player->id, 'tournoi_id' => $this->tournament->id, 'statut_id' => 42,
        ]);
    }

    public function test_legacy_null_capacity_and_dates_do_not_create_a_false_full_state(): void
    {
        $this->tournament->nombre_max_participants = null;
        $this->tournament->date_fin = null;
        $this->tournament->date_limite_inscription = null;
        $this->tournament->save();
        $this->actingAs($this->player)->get($this->registrationUrl())->assertOk()
            ->assertSee('Non limité')->assertSee('Au début du tournoi')->assertSee('Confirmer mon inscription');
        $this->postJson($this->registrationUrl())->assertOk();
        $this->tournament->date_debut = now()->subDay();
        $this->tournament->save();
        $this->actingAs($this->other)->postJson($this->registrationUrl())->assertConflict()
            ->assertJsonPath('code', ErrorHandler::TOURNAMENT_REGISTRATION_CLOSED);
    }

    public function test_a_generic_success_flash_does_not_pretend_the_user_is_registered(): void
    {
        $this->actingAs($this->player)->withSession(['success' => 'Tournoi créé avec succès.']);
        $this->get($this->registrationUrl())->assertOk()->assertSee('Confirmer mon inscription')
            ->assertDontSee('Ton inscription est enregistrée');
        $this->assertDatabaseCount('inscription', 0);
    }

    public function test_registration_database_failure_is_hidden_and_rolled_back(): void
    {
        config(['app.debug' => true]);
        TournamentRegistration::created(function (): void {
            throw new QueryException('sqlite', 'private SQL', [], new \PDOException('private details'));
        });
        try {
            $this->actingAs($this->player)->postJson($this->registrationUrl())->assertStatus(503)->assertExactJson([
                'code' => ErrorHandler::TOURNAMENT_REGISTRATION_FAILED,
                'message' => ErrorHandler::message(ErrorHandler::TOURNAMENT_REGISTRATION_FAILED),
            ]);
            $this->assertDatabaseCount('inscription', 0);
        } finally {
            TournamentRegistration::flushEventListeners();
        }
    }

    public function test_reactivation_failure_preserves_the_cancelled_state_and_html_redirect(): void
    {
        $registration = $this->registration($this->player, $this->tournament, RegistrationStatus::CANCELLED);
        TournamentRegistration::updating(function (): void {
            throw new QueryException('sqlite', 'private SQL', [], new \PDOException('private details'));
        });
        try {
            $this->actingAs($this->player)->post($this->registrationUrl())
                ->assertRedirect($this->registrationUrl())->assertSessionHas('error_code', ErrorHandler::TOURNAMENT_REGISTRATION_FAILED);
            $this->assertSame(RegistrationStatus::CANCELLED, $registration->fresh()->status->code);
        } finally {
            TournamentRegistration::flushEventListeners();
        }
    }

    public function test_missing_or_invalid_tournament_ids_return_404_not_500(): void
    {
        $this->actingAs($this->player);
        foreach (['99999', 'abc'] as $id) {
            $this->get('/tournoi/'.$id.'/inscription')->assertNotFound();
            $this->post('/tournoi/'.$id.'/inscription')->assertNotFound();
        }
    }

    private function registrationUrl(): string
    {
        return route('tournament.registration', ['id' => $this->tournament->id]);
    }

    private function registration(User $user, Tournament $tournament, string $code): TournamentRegistration
    {
        $registration = new TournamentRegistration;
        $registration->user()->associate($user);
        $registration->tournament()->associate($tournament);
        $registration->status()->associate(RegistrationStatus::where('code', $code)->firstOrFail());
        $registration->date_inscription = now()->subDays(2);
        $registration->save();

        return $registration;
    }

    private function createTournament(string $name, array $attributes = []): Tournament
    {
        $tournament = $this->tournament->replicate();
        $tournament->nom = $name;
        foreach ($attributes as $key => $value) {
            $tournament->{$key} = $value;
        }
        $tournament->save();

        return $tournament;
    }
}
