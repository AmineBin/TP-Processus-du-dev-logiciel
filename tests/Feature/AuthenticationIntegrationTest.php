<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\ErrorHandler;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep integration tests isolated from the configured application database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guests_cannot_access_tournament_pages_even_with_the_old_session_key(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $tournamentId = DB::table('tournoi')->value('id');

        $this->withSession(['utilisateur_id' => $user->id]);

        foreach (['/home', '/sport/fps', "/tournoi/{$tournamentId}/inscription"] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $this->post("/tournoi/{$tournamentId}/inscription")->assertRedirect('/login');
        $this->assertDatabaseCount('inscription', 0);
    }

    public function test_frontend_forms_match_the_controller_contract(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="email"', false)
            ->assertSee('name="password"', false);

        $response = $this->get('/register')->assertOk();

        foreach (array_keys($this->registrationData()) as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
    }

    public function test_login_uses_the_custom_password_column_and_persists_the_session(): void
    {
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'password'])
            ->assertRedirect('/home')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs(User::where('email', 'test@example.com')->firstOrFail());
        Auth::forgetGuards();

        $this->get('/home')->assertOk()->assertSee('Jeux vidéo')->assertSee('FPS');
        $this->get('/sport/fps')->assertOk()->assertSee('Tournoi FPS de démonstration');
        $this->get('/')->assertRedirect('/home');
        $this->get('/login')->assertRedirect('/home');
    }

    public function test_invalid_credentials_return_errors_without_authenticating(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', 'test@example.com');

        $this->assertGuest();
    }

    public function test_disabled_accounts_cannot_log_in(): void
    {
        DB::table('utilisateur')->where('email', 'test@example.com')->update(['est_actif' => false]);

        $this->from('/login')->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_account_disabled_after_login_loses_access(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);
        DB::table('utilisateur')->where('id', $user->id)->update(['est_actif' => false]);
        $user->refresh();

        $this->get('/home')->assertRedirect('/login')->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_registration_creates_a_user_who_can_log_in(): void
    {
        $data = $this->registrationData();

        $this->post('/register', $data)->assertRedirect('/login')
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame($data['username'], $user->nom_utilisateur);
        $this->assertSame($data['first_name'], $user->prenom);
        $this->assertSame($data['last_name'], $user->nom);
        $this->assertSame($data['phone'], $user->numero_telephone);
        $this->assertTrue(Hash::check($data['password'], $user->mot_de_passe_hash));
        $this->assertArrayNotHasKey('mot_de_passe_hash', $user->toArray());
        $this->assertGuest();

        $this->get('/login')->assertOk()->assertSee('Compte créé avec succès.');
        $this->post('/login', ['email' => $data['email'], 'password' => $data['password']])
            ->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_rejects_phone_numbers_longer_than_ten_characters(): void
    {
        $this->postJson('/register', array_replace($this->registrationData(), [
            'phone' => '06123456789',
        ]))->assertUnprocessable()->assertJsonValidationErrors('phone');

        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_registration_rejects_duplicate_email_and_username(): void
    {
        $this->from('/register')->post('/register', array_replace($this->registrationData(), [
            'username' => 'testeur',
            'email' => 'test@example.com',
        ]))->assertRedirect('/register')->assertSessionHasErrors(['username', 'email']);

        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_registration_rejects_invalid_birth_date_and_password_confirmation(): void
    {
        $this->from('/register')->post('/register', array_replace($this->registrationData(), [
            'birth_date' => now()->addDay()->toDateString(),
            'password_confirmation' => 'different-password',
        ]))->assertRedirect('/register')->assertSessionHasErrors(['birth_date', 'password']);

        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $this->actingAs(User::where('email', 'test@example.com')->firstOrFail());
        $this->withSession(['private_data' => 'must-be-removed']);

        $this->post('/logout')->assertRedirect('/login')->assertSessionHas('success')
            ->assertSessionMissing('private_data');
        $this->assertGuest();
        Auth::forgetGuards();
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_register_for_a_tournament(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $tournamentId = DB::table('tournoi')->value('id');
        $url = "/tournoi/{$tournamentId}/inscription";
        $this->actingAs($user);

        $this->get($url)->assertOk()->assertSee($user->nom_utilisateur);
        $this->post($url)->assertRedirect($url)->assertSessionHas('success');
        $this->assertDatabaseHas('inscription', [
            'utilisateur_id' => $user->id,
            'tournoi_id' => $tournamentId,
        ]);

        $this->from($url)->post($url)->assertRedirect($url)->assertSessionHas('error');
        $this->assertDatabaseCount('inscription', 1);
    }

    public function test_registration_uses_the_role_code_instead_of_a_fixed_id(): void
    {
        Role::query()->where('code', Role::USER_CODE)->update(['code' => 'LEGACY_USER']);
        $role = new Role(['code' => Role::USER_CODE, 'libelle' => 'Utilisateur']);
        $role->id = 42;
        $role->save();

        $this->post('/register', array_replace($this->registrationData(), [
            'role_id' => Role::query()->where('code', Role::ADMIN_CODE)->value('id'),
            'est_actif' => false,
        ]))->assertRedirect('/login')->assertSessionHasNoErrors();

        $user = User::where('email', 'nouveau@example.com')->firstOrFail();
        $this->assertSame(42, $user->role_id);
        $this->assertTrue($user->est_actif);
        $this->assertSame(Role::USER_CODE, $user->role->code);
    }

    public function test_missing_standard_role_returns_a_controlled_error(): void
    {
        Role::query()->where('code', Role::USER_CODE)->update(['code' => 'LEGACY_USER']);

        $this->post('/register', $this->registrationData())->assertRedirect('/register')
            ->assertSessionHas('error_code', ErrorHandler::AUTH_ROLE_UNAVAILABLE);
        $this->get('/register')->assertOk()
            ->assertSee(ErrorHandler::message(ErrorHandler::AUTH_ROLE_UNAVAILABLE))
            ->assertDontSee('SQLSTATE');
        $this->postJson('/register', $this->registrationData())->assertStatus(503)
            ->assertJsonPath('code', ErrorHandler::AUTH_ROLE_UNAVAILABLE);
        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_role_seeder_repairs_missing_roles_without_touching_accounts_or_existing_ids(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $adminId = Role::query()->where('code', Role::ADMIN_CODE)->value('id');
        Role::query()->where('code', Role::USER_CODE)->update(['code' => 'LEGACY_USER']);

        $this->seed(RoleSeeder::class);
        $standardId = Role::query()->where('code', Role::USER_CODE)->value('id');
        $this->seed(RoleSeeder::class);

        $this->assertNotNull($standardId);
        $this->assertSame($standardId, Role::query()->where('code', Role::USER_CODE)->value('id'));
        $this->assertSame($adminId, Role::query()->where('code', Role::ADMIN_CODE)->value('id'));
        $this->assertDatabaseCount('role_utilisateur', 3);
        $this->assertDatabaseCount('utilisateur', 2);
        $this->assertSame($user->mot_de_passe_hash, $user->fresh()->mot_de_passe_hash);
    }

    public function test_json_clients_receive_the_shared_error_contract(): void
    {
        $this->getJson('/home')->assertUnauthorized()->assertExactJson([
            'code' => ErrorHandler::AUTH_REQUIRED,
            'message' => ErrorHandler::message(ErrorHandler::AUTH_REQUIRED),
        ]);
        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'wrong'])
            ->assertUnauthorized()->assertJsonPath('code', ErrorHandler::AUTH_INVALID_CREDENTIALS)
            ->assertJsonValidationErrors('email');
        $this->postJson('/register', [])->assertUnprocessable()
            ->assertJsonPath('code', ErrorHandler::VALIDATION_FAILED)
            ->assertJsonValidationErrors(['username', 'email', 'phone', 'birth_date', 'password']);
    }

    public function test_validation_never_flashes_passwords_and_renders_french_messages(): void
    {
        $data = array_replace($this->registrationData(), ['password_confirmation' => 'wrong']);

        $this->post('/register', $data)->assertRedirect('/register')
            ->assertSessionHas('error_code', ErrorHandler::VALIDATION_FAILED)
            ->assertSessionHasErrors('password')
            ->assertSessionHasInput('username', 'nouveau')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
        // Rejoue le cookie comme le navigateur pour relire la session JSON stockée.
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/register')->assertOk()->assertSee(ErrorHandler::message('PASSWORD_CONFIRMATION'));
    }

    public function test_login_rejects_non_string_passwords_before_authentication(): void
    {
        $this->postJson('/login', ['email' => 'test@example.com', 'password' => ['invalid']])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertGuest();
    }

    public function test_guest_middleware_blocks_all_authentication_forms_for_connected_users(): void
    {
        $this->actingAs(User::where('email', 'test@example.com')->firstOrFail());

        foreach (['/login', '/register'] as $url) {
            $this->get($url)->assertRedirect('/home');
            $this->post($url, $this->registrationData())->assertRedirect('/home');
            $this->getJson($url)->assertStatus(409)
                ->assertJsonPath('code', ErrorHandler::AUTH_ALREADY_AUTHENTICATED);
        }

        $this->assertDatabaseCount('utilisateur', 2);
    }

    public function test_disabled_accounts_lose_session_data_and_receive_json_errors(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $user->est_actif = false;
        $user->save();
        $this->actingAs($user)->withSession(['private_data' => 'must-be-removed']);

        $this->getJson('/sport/fps')->assertForbidden()
            ->assertJsonPath('code', ErrorHandler::AUTH_INACTIVE)
            ->assertSessionMissing('private_data');
        $this->assertGuest();
    }

    public function test_login_rotates_the_session_and_resumes_the_requested_page(): void
    {
        $this->get('/sport/fps')->assertRedirect('/login');
        $sessionId = session()->getId();

        $this->post('/login', ['email' => 'test@example.com', 'password' => 'password'])
            ->assertRedirect('/sport/fps');
        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_json_registration_login_and_logout_keep_session_authentication(): void
    {
        $data = $this->registrationData();
        $this->postJson('/register', $data)->assertCreated()
            ->assertJsonPath('message', 'Compte créé avec succès.');
        $this->assertGuest();
        $this->postJson('/login', ['email' => $data['email'], 'password' => $data['password']])
            ->assertOk();
        $this->assertAuthenticated();
        Auth::forgetGuards();
        $this->get('/home')->assertOk();
        $this->postJson('/logout')->assertOk();
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_with_a_public_error_code(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'wrong'])
                ->assertUnauthorized();
        }

        $this->postJson('/login', ['email' => 'test@example.com', 'password' => 'wrong'])
            ->assertStatus(429)->assertHeader('Retry-After')
            ->assertJsonPath('code', ErrorHandler::AUTH_RATE_LIMITED);
    }

    public function test_registration_hides_database_errors_even_in_debug_mode(): void
    {
        config(['app.debug' => true]);
        User::creating(function (): void {
            throw new QueryException('sqlite', 'sensitive SQL', [], new \PDOException('private details'));
        });

        try {
            $this->postJson('/register', $this->registrationData())->assertStatus(503)
                ->assertExactJson([
                    'code' => ErrorHandler::AUTH_REGISTRATION_FAILED,
                    'message' => ErrorHandler::message(ErrorHandler::AUTH_REGISTRATION_FAILED),
                ]);
            $this->assertDatabaseCount('utilisateur', 2);
        } finally {
            User::flushEventListeners();
        }
    }

    public function test_registration_handles_a_uniqueness_race_without_exposing_sql(): void
    {
        User::creating(function (): void {
            throw new UniqueConstraintViolationException('sqlite', 'private SQL', [], new \PDOException('duplicate'));
        });

        try {
            $this->postJson('/register', $this->registrationData())->assertStatus(409)
                ->assertExactJson([
                    'code' => ErrorHandler::AUTH_ACCOUNT_CONFLICT,
                    'message' => ErrorHandler::message(ErrorHandler::AUTH_ACCOUNT_CONFLICT),
                ]);
        } finally {
            User::flushEventListeners();
        }
    }

    private function registrationData(): array
    {
        return [
            'username' => 'nouveau',
            'last_name' => 'Dupont',
            'first_name' => 'Camille',
            'email' => 'nouveau@example.com',
            'phone' => '0612345678',
            'birth_date' => '2000-01-01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
    }
}
