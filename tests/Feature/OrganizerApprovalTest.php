<?php

namespace Tests\Feature;

use App\Actions\OrganizerRequests\ApproveOrganizerRequest;
use App\Actions\OrganizerRequests\RejectOrganizerRequest;
use App\Exceptions\OrganizerRequestException;
use App\Models\OrganizerRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ErrorHandler;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrganizerApprovalTest extends TestCase
{
    private const STORE = '/organizer-requests';

    private const ADMIN = '/admin/organizer-requests';

    private User $admin;

    private User $player;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->player = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_submission_creates_only_a_pending_request_and_hashes_the_password(): void
    {
        $payload = $this->payload();
        $response = $this->postJson(self::STORE, $payload)->assertCreated()
            ->assertJsonPath('data.status', OrganizerRequest::PENDING);
        $application = OrganizerRequest::findOrFail($response->json('data.id'));

        $this->assertTrue(Hash::check($payload['password'], $application->mot_de_passe_hash));
        $this->assertSame(Role::ORGANIZER_CODE, $application->requestedRole->code);
        $this->assertNull($application->traite_par);
        $this->assertNull($application->utilisateur_cree_id);
        $this->assertArrayNotHasKey('mot_de_passe_hash', $application->toArray());
        $response->assertDontSee($payload['password'], false)->assertDontSee($application->mot_de_passe_hash, false)
            ->assertJsonMissingPath('data.email_en_attente')->assertJsonMissingPath('data.role_id');
        $this->assertDatabaseCount('utilisateur', 2);
        $this->assertGuest();
        $this->postJson('/login', ['email' => $payload['email'], 'password' => $payload['password']])
            ->assertUnauthorized();
    }

    public function test_email_is_normalized_and_optional_names_can_be_omitted(): void
    {
        $payload = $this->payload();
        unset($payload['first_name'], $payload['last_name']);
        $payload['email'] = '  Candidat@EXAMPLE.TEST  ';
        $this->postJson(self::STORE, $payload)->assertCreated();
        $application = OrganizerRequest::firstOrFail();
        $this->assertSame('candidat@example.test', $application->email);
        $this->assertNull($application->prenom);
        $this->assertNull($application->nom);
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertOk();
        $this->assertDatabaseHas('utilisateur', ['email' => 'candidat@example.test', 'prenom' => null, 'nom' => null]);
    }

    public function test_routes_use_the_existing_web_urls_without_an_api_prefix(): void
    {
        $this->assertSame('/organizer-requests', parse_url(route('organizer-requests.store'), PHP_URL_PATH));
        $this->assertSame('/admin/organizer-requests', parse_url(route('admin.organizer-requests.index'), PHP_URL_PATH));
        $this->postJson('/api/v1/organizer-requests', $this->payload())->assertNotFound();
        $this->getJson('/api/v1/admin/organizer-requests')->assertNotFound();
    }

    public function test_web_endpoints_return_json_even_without_an_accept_header(): void
    {
        $this->post(self::STORE, [])->assertUnprocessable()->assertJsonPath('code', ErrorHandler::VALIDATION_FAILED);
        $this->get(self::ADMIN)->assertUnauthorized()->assertJsonPath('code', ErrorHandler::AUTH_REQUIRED);
        $this->post(self::STORE, $this->payload())->assertCreated()->assertJsonPath('data.status', OrganizerRequest::PENDING);
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_submission_fields_never_create_accounts(string $field, mixed $value): void
    {
        $errorField = $field === 'password_confirmation' ? 'password' : $field;
        $this->postJson(self::STORE, [...$this->payload(), $field => $value])->assertUnprocessable()
            ->assertJsonPath('code', ErrorHandler::VALIDATION_FAILED)->assertJsonValidationErrors($errorField);
        $this->assertDatabaseCount('demande_inscription', 0);
        $this->assertDatabaseCount('utilisateur', 2);
    }

    /** @return array<string, array{string, mixed}> Contraintes du contrat et champs de traitement interdits. */
    public static function invalidFields(): array
    {
        return [
            'pseudo absent' => ['username', null],
            'pseudo trop long' => ['username', str_repeat('a', 51)],
            'nom trop long' => ['last_name', str_repeat('a', 51)],
            'email invalide' => ['email', 'invalid'],
            'téléphone absent' => ['phone', null],
            'téléphone trop long' => ['phone', '06123456789'],
            'date invalide' => ['birth_date', 'invalid'],
            'date future' => ['birth_date', '2999-01-01'],
            'mot de passe court' => ['password', 'short'],
            'mot de passe tableau' => ['password', ['invalid']],
            'confirmation différente' => ['password_confirmation', 'different'],
            'rôle numérique injecté' => ['role_id', 2],
            'rôle admin injecté' => ['role', 'ADMIN'],
            'statut SQL injecté' => ['statut', 'APPROUVEE'],
            'statut JSON injecté' => ['status', 'APPROUVEE'],
            'administrateur injecté' => ['traite_par', 1],
            'date traitement injectée' => ['date_traitement', '2026-01-01'],
            'utilisateur créé injecté' => ['utilisateur_cree_id', 1],
            'hachage injecté' => ['mot_de_passe_hash', 'HASH_TEST'],
        ];
    }

    public function test_bcrypt_byte_limit_is_validated_before_hashing_unicode_passwords(): void
    {
        $password = str_repeat('é', 40);
        $this->postJson(self::STORE, [
            ...$this->payload(), 'password' => $password, 'password_confirmation' => $password,
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('demande_inscription', 0);
    }

    public function test_existing_and_pending_identities_are_checked_without_case_sensitivity(): void
    {
        $this->postJson(self::STORE, [...$this->payload(), 'username' => 'TESTEUR', 'email' => 'TEST@EXAMPLE.COM'])
            ->assertUnprocessable()->assertJsonValidationErrors(['username', 'email']);
        $this->postJson(self::STORE, $this->payload())->assertCreated();
        $this->postJson(self::STORE, [
            ...$this->payload(), 'username' => 'CANDIDAT_ORGA', 'email' => 'CANDIDAT@EXAMPLE.TEST',
        ])->assertUnprocessable()->assertJsonValidationErrors(['username', 'email']);
        $this->assertDatabaseCount('demande_inscription', 1);
    }

    public function test_pending_database_constraints_protect_against_a_concurrent_duplicate(): void
    {
        $first = $this->application();
        try {
            $this->application(['email' => strtoupper($first->email)]);
            $this->fail('La contrainte unique doit refuser deux demandes en attente.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertDatabaseCount('demande_inscription', 1);
        }
    }

    public function test_pending_username_constraint_also_covers_case_variants(): void
    {
        $first = $this->application();
        try {
            $this->application(['nom_utilisateur' => strtoupper($first->nom_utilisateur)]);
            $this->fail('La contrainte unique du pseudo doit refuser le doublon.');
        } catch (UniqueConstraintViolationException $exception) {
            $this->assertDatabaseCount('demande_inscription', 1);
        }
    }

    public function test_submission_uniqueness_race_returns_a_safe_conflict(): void
    {
        OrganizerRequest::creating(function (): void {
            throw new UniqueConstraintViolationException('sqlite', 'private SQL', [], new \PDOException('duplicate'));
        });
        try {
            $this->postJson(self::STORE, $this->payload())->assertConflict()
                ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_CONFLICT)->assertDontSee('private SQL');
        } finally {
            OrganizerRequest::flushEventListeners();
        }
    }

    public function test_logged_in_users_cannot_submit_a_new_preaccount_application(): void
    {
        $this->actingAs($this->player)->postJson(self::STORE, $this->payload())->assertConflict()
            ->assertJsonPath('code', ErrorHandler::AUTH_ALREADY_AUTHENTICATED);
        $this->assertDatabaseCount('demande_inscription', 0);
    }

    public function test_all_admin_endpoints_require_authentication_before_looking_up_ids(): void
    {
        $application = $this->application();
        $this->get(self::ADMIN)->assertUnauthorized()->assertJsonPath('code', ErrorHandler::AUTH_REQUIRED);
        $this->get(self::ADMIN.'/'.$application->id)->assertUnauthorized();
        $this->post(self::ADMIN.'/'.$application->id.'/approve')->assertUnauthorized();
        $this->post(self::ADMIN.'/'.$application->id.'/reject', ['reason' => 'Refus'])->assertUnauthorized();
        $this->post(self::ADMIN.'/9999/approve')->assertUnauthorized();
        $this->assertDatabaseCount('utilisateur', 2);
        $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
    }

    public function test_players_and_organizers_cannot_read_or_process_the_queue(): void
    {
        $application = $this->application();
        foreach ([Role::USER_CODE, Role::ORGANIZER_CODE] as $roleCode) {
            $this->player->role()->associate(Role::where('code', $roleCode)->firstOrFail());
            $this->player->save();
            $this->actingAs($this->player);
            $this->get(self::ADMIN)->assertForbidden()->assertJsonPath('code', ErrorHandler::ADMIN_REQUIRED);
            $this->get(self::ADMIN.'/'.$application->id)->assertForbidden();
            $this->post(self::ADMIN.'/'.$application->id.'/approve')->assertForbidden();
            $this->post(self::ADMIN.'/'.$application->id.'/reject', ['reason' => 'Refus'])->assertForbidden();
        }
        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_disabled_admin_loses_access_and_the_session(): void
    {
        $application = $this->application();
        $this->admin->est_actif = false;
        $this->admin->save();
        $this->actingAs($this->admin)->getJson(self::ADMIN)->assertForbidden()
            ->assertJsonPath('code', ErrorHandler::AUTH_INACTIVE);
        $this->assertGuest();
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertForbidden();
        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_admin_list_is_pending_only_paginated_and_never_contains_hashes(): void
    {
        for ($index = 0; $index < 5; $index++) {
            $this->application();
        }
        $this->application([], OrganizerRequest::REJECTED);
        $invalidRole = $this->application();
        $invalidRole->requestedRole()->associate(Role::where('code', Role::ADMIN_CODE)->firstOrFail());
        $invalidRole->save();

        $response = $this->actingAs($this->admin)->getJson(self::ADMIN.'?per_page=2')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 5)->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('data.0.status', OrganizerRequest::PENDING)
            ->assertJsonPath('data.0.requested_role', Role::ORGANIZER_CODE)
            ->assertJsonMissingPath('data.0.mot_de_passe_hash')->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.email_en_attente');
        foreach (OrganizerRequest::all() as $application) {
            $response->assertDontSee($application->mot_de_passe_hash, false);
        }
        $this->getJson(self::ADMIN.'?page=2&per_page=2')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson(self::ADMIN.'?status=REFUSEE')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson(self::ADMIN.'?status=APPROUVEE')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_list_filters_and_pagination_are_validated(): void
    {
        $this->actingAs($this->admin)->getJson(self::ADMIN.'?status=UNKNOWN&per_page=101&page=0')
            ->assertUnprocessable()->assertJsonValidationErrors(['status', 'per_page', 'page']);
    }

    public function test_approval_copies_the_hash_creates_an_organizer_and_records_the_reviewer(): void
    {
        $application = $this->application();
        $hash = $application->mot_de_passe_hash;
        $this->actingAs($this->admin)->getJson(self::ADMIN.'/'.$application->id)->assertOk()
            ->assertJsonPath('data.email', $application->email)->assertDontSee($hash, false);
        $response = $this->postJson(self::ADMIN.'/'.$application->id.'/approve', [
            'role_id' => Role::where('code', Role::ADMIN_CODE)->value('id'),
            'user_id' => $this->player->id,
        ])->assertOk()->assertJsonPath('data.status', OrganizerRequest::APPROVED)->assertDontSee($hash, false);

        $user = User::where('email', $application->email)->firstOrFail();
        $this->assertSame($hash, $user->mot_de_passe_hash);
        $this->assertTrue(Hash::check('Password123!', $user->mot_de_passe_hash));
        $this->assertTrue($user->isOrganizer());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->est_actif);
        $application->refresh();
        $this->assertSame($this->admin->id, $application->traite_par);
        $this->assertNotNull($application->date_traitement);
        $this->assertSame($user->id, $application->utilisateur_cree_id);
        $this->assertSame('', $application->mot_de_passe_hash);
        $this->assertSame($user->id, $response->json('data.created_user_id'));
        $this->getJson(self::ADMIN)->assertJsonCount(0, 'data');
        $this->getJson(self::ADMIN.'?status=APPROUVEE')->assertJsonCount(1, 'data');

        $this->postJson('/logout')->assertOk();
        $this->postJson('/login', ['email' => $user->email, 'password' => 'Password123!'])->assertOk();
        $this->get('/tournament')->assertOk();
    }

    public function test_rejection_creates_no_account_and_allows_a_new_application(): void
    {
        $this->postJson(self::STORE, $this->payload())->assertCreated();
        $application = OrganizerRequest::firstOrFail();
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/reject', ['reason' => 'Informations à compléter.'])
            ->assertOk()->assertJsonPath('data.status', OrganizerRequest::REJECTED)
            ->assertJsonPath('data.rejection_reason', 'Informations à compléter.');
        $application->refresh();
        $this->assertSame('', $application->mot_de_passe_hash);
        $this->assertSame($this->admin->id, $application->traite_par);
        $this->assertNotNull($application->date_traitement);
        $this->assertNull($application->utilisateur_cree_id);
        $this->assertDatabaseCount('utilisateur', 2);
        $this->postJson('/logout')->assertOk();
        $this->postJson(self::STORE, $this->payload())->assertCreated();
        $this->assertDatabaseCount('demande_inscription', 2);
    }

    public function test_rejection_requires_a_reason_with_a_database_compatible_length(): void
    {
        $application = $this->application();
        $this->actingAs($this->admin);
        foreach ([null, '', str_repeat('a', 501), ['invalid']] as $reason) {
            $this->postJson(self::ADMIN.'/'.$application->id.'/reject', ['reason' => $reason])
                ->assertUnprocessable()->assertJsonValidationErrors('reason');
        }
        $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
    }

    public function test_a_processed_request_cannot_be_approved_or_rejected_again(): void
    {
        $this->actingAs($this->admin);
        foreach ([OrganizerRequest::APPROVED, OrganizerRequest::REJECTED] as $status) {
            $application = $this->application([], $status);
            foreach (['approve', 'reject'] as $action) {
                $this->postJson(self::ADMIN.'/'.$application->id.'/'.$action, ['reason' => 'Autre motif'])
                    ->assertConflict()->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_ALREADY_PROCESSED);
            }
        }
        $this->assertDatabaseCount('utilisateur', 2);
    }

    #[DataProvider('conflictingIdentityColumns')]
    public function test_account_created_after_submission_blocks_approval_without_overwriting_it(string $column): void
    {
        $application = $this->application();
        $attributes = [
            'nom_utilisateur' => 'compte_intermediaire',
            'email' => 'compte-intermediaire@example.test',
            'mot_de_passe_hash' => Hash::make('existing-password'),
            'date_naissance' => '1990-01-01',
            'numero_telephone' => '0600000000',
            'role_id' => Role::where('code', Role::USER_CODE)->value('id'),
        ];
        $attributes[$column] = strtoupper($application->{$column});
        $id = DB::table('utilisateur')->insertGetId($attributes);
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')
            ->assertConflict()->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_ACCOUNT_CONFLICT);
        $this->assertDatabaseCount('utilisateur', 3);
        $this->assertDatabaseHas('utilisateur', ['id' => $id, ...$attributes]);
        $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
    }

    /** @return array<string, array{string}> Les deux contraintes d’identité du compte. */
    public static function conflictingIdentityColumns(): array
    {
        return ['email' => ['email'], 'pseudo' => ['nom_utilisateur']];
    }

    public function test_no_admin_account_can_be_created_from_a_manually_forged_request(): void
    {
        $application = $this->application();
        $application->requestedRole()->associate(Role::where('code', Role::ADMIN_CODE)->firstOrFail());
        $application->save();
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertConflict()
            ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_INVALID_ROLE);
        $this->getJson(self::ADMIN.'/'.$application->id)->assertNotFound();
        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_role_resolution_does_not_depend_on_numeric_ids(): void
    {
        Role::where('code', Role::ORGANIZER_CODE)->update(['code' => 'LEGACY_ORGANIZER']);
        $role = new Role(['code' => Role::ORGANIZER_CODE, 'libelle' => 'Organisateur']);
        $role->id = 42;
        $role->save();
        $this->postJson(self::STORE, $this->payload())->assertCreated();
        $application = OrganizerRequest::firstOrFail();
        $this->assertSame(42, $application->role_id);
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertOk();
        $this->assertDatabaseHas('utilisateur', ['email' => $application->email, 'role_id' => 42]);
    }

    public function test_missing_organizer_code_during_review_does_not_create_a_user(): void
    {
        $application = $this->application();
        Role::where('code', Role::ORGANIZER_CODE)->update(['code' => 'LEGACY_ORGANIZER']);
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertStatus(503)
            ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_ROLE_UNAVAILABLE);
        $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_missing_organizer_role_refuses_submission_safely(): void
    {
        Role::where('code', Role::ORGANIZER_CODE)->delete();
        $this->postJson(self::STORE, $this->payload())->assertStatus(503)
            ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_ROLE_UNAVAILABLE);
        $this->assertDatabaseCount('demande_inscription', 0);
    }

    #[DataProvider('incompleteFields')]
    public function test_incomplete_sql_imports_are_not_approved(string $field, mixed $value): void
    {
        $application = $this->application([$field => $value]);
        $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertConflict()
            ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_INCOMPLETE);
        $this->assertDatabaseCount('utilisateur', 2);
        $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
    }

    /** @return array<string, array{string, mixed}> Anciennes demandes SQL incomplètes. */
    public static function incompleteFields(): array
    {
        return [
            'date absente' => ['date_naissance', null],
            'date future' => ['date_naissance', '2999-01-01'],
            'téléphone absent' => ['numero_telephone', null],
            'hachage fictif' => ['mot_de_passe_hash', 'HASH_TEST'],
        ];
    }

    public function test_submission_is_rate_limited_with_the_public_contract(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson(self::STORE, [])->assertUnprocessable();
        }
        $this->postJson(self::STORE, [])->assertStatus(429)->assertHeader('Retry-After')
            ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_RATE_LIMITED);
    }

    public function test_submission_rate_limit_does_not_consume_the_login_quota(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson(self::STORE, [])->assertUnprocessable();
        }
        $this->postJson(self::STORE, [])->assertStatus(429);
        $this->postJson('/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertOk();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_submission_database_failure_rolls_back_and_hides_details(): void
    {
        config(['app.debug' => true]);
        OrganizerRequest::created(function (): void {
            throw new QueryException('sqlite', 'private SQL', [], new \PDOException('private details'));
        });
        try {
            $this->postJson(self::STORE, $this->payload())->assertStatus(503)->assertExactJson([
                'code' => ErrorHandler::ORGANIZER_REQUEST_FAILED,
                'message' => ErrorHandler::message(ErrorHandler::ORGANIZER_REQUEST_FAILED),
            ]);
            $this->assertDatabaseCount('demande_inscription', 0);
        } finally {
            OrganizerRequest::flushEventListeners();
        }
    }

    public function test_approval_failure_rolls_back_both_the_account_and_the_request(): void
    {
        $application = $this->application();
        $hash = $application->mot_de_passe_hash;
        OrganizerRequest::updated(function (): void {
            throw new QueryException('sqlite', 'private SQL', [], new \PDOException('private details'));
        });
        try {
            $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertStatus(503)
                ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_FAILED)->assertDontSee('private SQL');
            $this->assertDatabaseCount('utilisateur', 2);
            $application->refresh();
            $this->assertSame(OrganizerRequest::PENDING, $application->statut);
            $this->assertNull($application->traite_par);
            $this->assertNull($application->utilisateur_cree_id);
            $this->assertSame($hash, $application->mot_de_passe_hash);
        } finally {
            OrganizerRequest::flushEventListeners();
        }
    }

    public function test_rejection_failure_does_not_clear_the_pending_hash_or_state(): void
    {
        $application = $this->application();
        $hash = $application->mot_de_passe_hash;
        OrganizerRequest::updated(function (): void {
            throw new QueryException('sqlite', 'private SQL', [], new \PDOException('private details'));
        });
        try {
            $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/reject', ['reason' => 'Refus'])
                ->assertStatus(503)->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_FAILED);
            $application->refresh();
            $this->assertSame(OrganizerRequest::PENDING, $application->statut);
            $this->assertSame($hash, $application->mot_de_passe_hash);
            $this->assertNull($application->motif_refus);
            $this->assertNull($application->traite_par);
            $this->assertDatabaseCount('utilisateur', 2);
        } finally {
            OrganizerRequest::flushEventListeners();
        }
    }

    public function test_approval_unique_account_race_is_a_conflict_not_a_partial_approval(): void
    {
        $application = $this->application();
        User::creating(function (): void {
            throw new UniqueConstraintViolationException('sqlite', 'private SQL', [], new \PDOException('duplicate'));
        });
        try {
            $this->actingAs($this->admin)->postJson(self::ADMIN.'/'.$application->id.'/approve')->assertConflict()
                ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_ACCOUNT_CONFLICT);
            $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
        } finally {
            User::flushEventListeners();
        }
    }

    public function test_validation_database_failures_are_json_and_do_not_expose_sql(): void
    {
        config(['app.debug' => true]);
        Schema::drop('demande_inscription');
        $this->post(self::STORE, $this->payload())->assertStatus(503)->assertExactJson([
            'code' => ErrorHandler::ORGANIZER_REQUEST_FAILED,
            'message' => ErrorHandler::message(ErrorHandler::ORGANIZER_REQUEST_FAILED),
        ]);
    }

    public function test_web_csrf_errors_use_the_public_contract_even_without_accept_header(): void
    {
        config(['app.debug' => true]);
        Route::post(self::STORE.'/csrf-test', function (): void {
            throw new TokenMismatchException('private framework detail');
        })->name('organizer-requests.csrf-test');
        $this->post(self::STORE.'/csrf-test')->assertStatus(419)->assertExactJson([
            'code' => ErrorHandler::CSRF_TOKEN_MISMATCH,
            'message' => ErrorHandler::message(ErrorHandler::CSRF_TOKEN_MISMATCH),
        ]);
    }

    public function test_unknown_request_ids_return_404_to_admins(): void
    {
        $this->actingAs($this->admin)->getJson(self::ADMIN.'/9999')->assertNotFound()
            ->assertJsonPath('code', ErrorHandler::ORGANIZER_REQUEST_NOT_FOUND);
        $this->postJson(self::ADMIN.'/9999/approve')->assertNotFound();
        $this->postJson(self::ADMIN.'/9999/reject', ['reason' => 'Refus'])->assertNotFound();
    }

    public function test_actions_also_refuse_a_non_admin_outside_http(): void
    {
        $application = $this->application();
        foreach ([
            fn () => (new ApproveOrganizerRequest)->handle((string) $application->id, $this->player),
            fn () => (new RejectOrganizerRequest)->handle((string) $application->id, $this->player, 'Refus'),
        ] as $action) {
            try {
                $action();
                $this->fail('Un administrateur est requis.');
            } catch (OrganizerRequestException $exception) {
                $this->assertSame(ErrorHandler::ADMIN_REQUIRED, $exception->errorCode);
                $this->assertSame(403, $exception->httpStatus);
            }
        }
        $this->assertSame(OrganizerRequest::PENDING, $application->fresh()->statut);
    }

    private function payload(): array
    {
        return [
            'username' => 'candidat_orga', 'email' => 'candidat@example.test',
            'first_name' => 'Camille', 'last_name' => 'Dupont',
            'phone' => '0612345678', 'birth_date' => '2000-01-01',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
        ];
    }

    private function application(array $attributes = [], string $status = OrganizerRequest::PENDING): OrganizerRequest
    {
        $index = ++$this->sequence;
        $application = new OrganizerRequest([
            'nom_utilisateur' => 'candidat_'.$index,
            'email' => 'candidat'.$index.'@example.test',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'prenom' => 'Camille', 'nom' => 'Dupont',
            'numero_telephone' => '0612345678', 'date_naissance' => '2000-01-01',
            ...$attributes,
        ]);
        $application->requestedRole()->associate(Role::where('code', Role::ORGANIZER_CODE)->firstOrFail());
        $application->statut = $status;
        $application->save();

        return $application->refresh();
    }
}
