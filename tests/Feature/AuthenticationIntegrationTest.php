<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
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
