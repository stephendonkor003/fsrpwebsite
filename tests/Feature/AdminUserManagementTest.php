<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_management_requires_an_active_administrator_for_read_and_write_routes(): void
    {
        $target = User::factory()->create();
        $routes = [
            ['get', route('admin.users.index'), []],
            ['get', route('admin.users.create'), []],
            ['get', route('admin.users.edit', $target), []],
            ['post', route('admin.users.store'), $this->userPayload()],
            ['put', route('admin.users.update', $target), $this->userPayload()],
            ['patch', route('admin.users.status', $target), ['is_active' => false]],
        ];

        foreach ($routes as [$method, $url, $payload]) {
            $this->{$method}($url, $payload)->assertRedirect(route('login'));
        }

        foreach ([['is_admin' => false, 'is_active' => true], ['is_admin' => true, 'is_active' => false]] as $attributes) {
            $unauthorized = User::factory()->create($attributes);

            foreach ($routes as [$method, $url, $payload]) {
                $this->actingAs($unauthorized)->{$method}($url, $payload)->assertForbidden();
                $this->assertGuest();
            }
        }

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => $target->name]);
    }

    public function test_user_create_and_edit_forms_render_and_hide_stored_credentials(): void
    {
        $administrator = $this->administrator();
        $target = User::factory()->create(['remember_token' => 'private-remember-token']);

        $this->actingAs($administrator)->get(route('admin.users.create'))
            ->assertOk()->assertViewIs('admin.users.form')->assertViewHas('formMode', 'create');
        $this->get(route('admin.users.edit', $target))
            ->assertOk()->assertViewHas('formMode', 'edit')->assertSee($target->email)
            ->assertDontSee($target->password, false)->assertDontSee('private-remember-token');
    }

    public function test_users_can_be_searched_and_filtered_without_leaking_unmatched_accounts(): void
    {
        $administrator = $this->administrator();
        $matching = User::factory()->create(['name' => 'Ada Example', 'email' => 'ada@example.test', 'is_admin' => true, 'is_active' => false]);
        User::factory()->create(['name' => 'Ada Active', 'is_admin' => true, 'is_active' => true]);
        User::factory()->create(['name' => 'Ada Standard', 'is_admin' => false, 'is_active' => false]);

        $this->actingAs($administrator)->get(route('admin.users.index', ['search' => 'ADA', 'status' => 'disabled', 'access' => 'admin']))
            ->assertOk()->assertSee('Ada Example')->assertDontSee('Ada Active')->assertDontSee('Ada Standard')
            ->assertViewHas('users', fn ($users): bool => $users->total() === 1 && $users->first()->is($matching))
            ->assertViewHas('summary', ['total' => 4, 'active' => 2, 'disabled' => 2, 'administrators' => 3]);

        $this->get(route('admin.users.index', ['search' => 'ADA@EXAMPLE.TEST']))
            ->assertViewHas('users', fn ($users): bool => $users->total() === 1 && $users->first()->is($matching));
    }

    public function test_user_list_paginates_and_escapes_account_names(): void
    {
        $administrator = $this->administrator();
        User::factory()->count(25)->create(['is_admin' => false, 'is_active' => true]);
        $unsafeName = '<script>alert("account")</script>';
        User::factory()->create(['name' => $unsafeName, 'is_admin' => false, 'is_active' => true]);

        $this->actingAs($administrator)->get(route('admin.users.index', ['per_page' => 15]))
            ->assertViewHas('users', fn ($users): bool => $users->total() === 27 && $users->count() === 15);
        $this->get(route('admin.users.index', ['search' => '<script>']))
            ->assertSee($unsafeName)->assertDontSee($unsafeName, false);
    }

    public function test_an_administrator_can_create_a_user_with_a_normalized_email_and_hashed_password(): void
    {
        $administrator = $this->administrator();
        $payload = array_merge($this->userPayload(), [
            'name' => '  New Administrator  ',
            'email' => '  NEW.ADMIN@EXAMPLE.TEST  ',
            'remember_token' => 'injected-token',
            'email_verified_at' => '2026-01-01 00:00:00',
        ]);

        $this->actingAs($administrator)->post(route('admin.users.store'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'new.admin@example.test')->firstOrFail();
        $this->assertSame('New Administrator', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertNull($user->remember_token);
        $this->assertNull($user->email_verified_at);
    }

    public function test_invalid_new_user_input_cannot_create_accounts_or_flash_passwords(): void
    {
        $administrator = $this->administrator();
        $invalidInputs = [
            ['name' => '', 'email' => 'invalid', 'is_admin' => 'invalid', 'is_active' => 'invalid'],
            ['password' => 'weak', 'password_confirmation' => 'weak'],
            ['password_confirmation' => 'DifferentPassword!123'],
        ];

        foreach ($invalidInputs as $invalid) {
            $this->actingAs($administrator)->from(route('admin.users.create'))
                ->post(route('admin.users.store'), array_merge($this->userPayload(), $invalid))
                ->assertRedirect(route('admin.users.create'))->assertSessionHasErrors();
            $this->assertNull(session()->getOldInput('password'));
            $this->assertNull(session()->getOldInput('password_confirmation'));
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_create_and_update_reject_case_insensitive_email_collisions(): void
    {
        $administrator = $this->administrator();
        User::factory()->create(['email' => 'EXISTING@EXAMPLE.TEST']);
        $target = User::factory()->create(['email' => 'target@example.test']);
        $payload = array_merge($this->userPayload(), ['email' => 'existing@example.test']);

        $this->actingAs($administrator)->post(route('admin.users.store'), $payload)
            ->assertSessionHasErrors(['email' => 'This email address is already assigned to another user.']);
        $this->put(route('admin.users.update', $target), $payload)
            ->assertSessionHasErrors(['email']);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'email' => 'target@example.test']);
    }

    public function test_an_administrator_can_update_user_details_and_preserve_an_unchanged_password(): void
    {
        $administrator = $this->administrator();
        $target = User::factory()->create(['email' => 'target@example.test', 'is_admin' => true, 'is_active' => true]);
        $originalPassword = $target->password;

        $this->actingAs($administrator)->put(route('admin.users.update', $target), [
            'name' => 'Updated User',
            'email' => 'TARGET@EXAMPLE.TEST',
            'is_admin' => true,
            'is_active' => true,
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated User', 'email' => 'target@example.test', 'password' => $originalPassword]);
    }

    public function test_disabling_user_sign_in_removes_existing_sessions_and_remember_access(): void
    {
        config(['session.driver' => 'database']);
        $administrator = $this->administrator();
        $target = User::factory()->create(['is_admin' => true, 'is_active' => true, 'remember_token' => 'old-remember-token']);
        DB::table('browser_sessions')->insert([
            ['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'unrelated-session', 'user_id' => $administrator->id, 'payload' => '', 'last_activity' => time()],
        ]);

        $this->actingAs($administrator)->patch(route('admin.users.status', $target), ['is_active' => false])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
        $this->assertSame(1, $target->fresh()->session_version);
        $this->assertDatabaseMissing('browser_sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('browser_sessions', ['id' => 'unrelated-session']);
        $this->assertNotSame('old-remember-token', $target->fresh()->remember_token);
    }

    public function test_reenabling_an_administrator_restores_password_login(): void
    {
        $administrator = $this->administrator();
        $target = User::factory()->create(['is_admin' => true, 'is_active' => false, 'password' => 'SecurePassword!123']);

        $this->actingAs($administrator)->patch(route('admin.users.status', $target), ['is_active' => true])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $this->post(route('admin.logout'));
        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'SecurePassword!123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($target);
    }

    public function test_disabled_and_standard_users_cannot_log_in_with_correct_passwords(): void
    {
        foreach ([['is_admin' => true, 'is_active' => false], ['is_admin' => false, 'is_active' => true]] as $attributes) {
            $user = User::factory()->create(array_merge($attributes, ['password' => 'SecurePassword!123']));

            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'SecurePassword!123'])
                ->assertSessionHasErrors(['email']);

            $this->assertGuest();
        }
    }

    public function test_administrators_cannot_disable_themselves_or_remove_their_own_administrator_access(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->patch(route('admin.users.status', $administrator), ['is_active' => false])
            ->assertSessionHasErrors(['is_active' => 'You cannot disable your own account.']);
        $this->put(route('admin.users.update', $administrator), array_merge($this->userPayload(), [
            'email' => $administrator->email,
            'is_admin' => false,
            'is_active' => false,
        ]))->assertSessionHasErrors(['is_admin', 'is_active']);

        $this->assertDatabaseHas('users', ['id' => $administrator->id, 'is_admin' => true, 'is_active' => true]);
    }

    public function test_removing_administrator_access_also_revokes_sessions_and_remember_access(): void
    {
        config(['session.driver' => 'database']);
        $administrator = $this->administrator();
        $target = $this->administrator();
        DB::table('browser_sessions')->insert(['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);
        $oldToken = $target->remember_token;

        $this->actingAs($administrator)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'is_admin' => false,
            'is_active' => true,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_admin' => false, 'is_active' => true]);
        $this->assertDatabaseMissing('browser_sessions', ['id' => 'target-session']);
        $this->assertNotSame($oldToken, $target->fresh()->remember_token);
    }

    public function test_updating_another_users_password_revokes_sessions_and_stores_only_the_new_hash(): void
    {
        config(['session.driver' => 'database']);
        $administrator = $this->administrator();
        $target = $this->administrator();
        DB::table('browser_sessions')->insert(['id' => 'target-session', 'user_id' => $target->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($administrator)->put(route('admin.users.update', $target), array_merge($this->userPayload(), ['email' => $target->email]))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('SecurePassword!123', $target->fresh()->password));
        $this->assertDatabaseMissing('browser_sessions', ['id' => 'target-session']);
        $this->assertNotSame($target->remember_token, $target->fresh()->remember_token);
    }

    public function test_updating_own_password_requires_signing_in_again(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->put(route('admin.users.update', $administrator), array_merge($this->userPayload(), ['email' => $administrator->email]))
            ->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(Hash::check('SecurePassword!123', $administrator->fresh()->password));
    }

    public function test_invalid_password_updates_leave_credentials_and_account_details_unchanged(): void
    {
        $administrator = $this->administrator();
        $target = $this->administrator();

        foreach ([
            ['password' => 'weak', 'password_confirmation' => 'weak'],
            ['password' => 'SecurePassword!123', 'password_confirmation' => 'MismatchPassword!456'],
        ] as $invalidPassword) {
            $this->actingAs($administrator)->put(route('admin.users.update', $target), array_merge(
                $this->userPayload(),
                ['email' => $target->email],
                $invalidPassword,
            ))->assertSessionHasErrors(['password']);
        }

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => $target->name,
            'password' => $target->password,
            'remember_token' => $target->remember_token,
        ]);
    }

    public function test_revoked_accounts_are_logged_out_on_their_next_protected_request(): void
    {
        $user = $this->administrator();
        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();
        $user->update(['is_active' => false]);

        $this->get(route('admin.users.index'))->assertForbidden();

        $this->assertGuest();
    }

    public function test_disabling_then_reenabling_an_account_does_not_revive_its_old_session(): void
    {
        $administrator = $this->administrator();
        $target = $this->administrator();

        $this->actingAs($administrator)->patch(route('admin.users.status', $target), ['is_active' => false])
            ->assertSessionHasNoErrors();
        $this->patch(route('admin.users.status', $target), ['is_active' => true])
            ->assertSessionHasNoErrors();
        $this->actingAs($target->fresh())->withSession(['account_session_version' => 0])
            ->get(route('admin.users.index'))->assertForbidden();

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true, 'session_version' => 1]);
    }

    public function test_new_login_uses_the_current_session_version_after_access_is_restored(): void
    {
        $user = $this->administrator();
        $user->forceFill(['session_version' => 3, 'password' => 'SecurePassword!123'])->save();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'SecurePassword!123'])
            ->assertSessionHas('account_session_version', 3)->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.users.index'))->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_unrevoked_legacy_session_remains_valid_without_a_version_snapshot(): void
    {
        $user = $this->administrator();

        $this->actingAs($user)->get(route('admin.users.index'))->assertOk();

        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_remember_cookie_restores_the_current_account_session_version(): void
    {
        $user = $this->administrator();
        $user->forceFill(['session_version' => 3])->save();
        $guard = Auth::guard();
        $recaller = $user->id.'|'.$user->remember_token.'|'.$guard->hashPasswordForCookie($user->password);

        $this->withCookie($guard->getRecallerName(), $recaller)->get(route('admin.users.index'))
            ->assertOk()->assertSessionHas('account_session_version', 3);

        $this->assertAuthenticatedAs($user);
    }

    public function test_rotated_remember_cookie_cannot_restore_access_after_disable_and_reenable(): void
    {
        $administrator = $this->administrator();
        $target = $this->administrator();
        $guard = Auth::guard();
        $cookieName = $guard->getRecallerName();
        $recaller = $target->id.'|'.$target->remember_token.'|'.$guard->hashPasswordForCookie($target->password);

        $this->actingAs($administrator)->patch(route('admin.users.status', $target), ['is_active' => false])
            ->assertSessionHasNoErrors();
        $this->patch(route('admin.users.status', $target), ['is_active' => true])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.logout'));
        Auth::forgetGuards();
        $this->withCookie($cookieName, $recaller)->get(route('admin.users.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_remember_restoration_does_not_overwrite_an_existing_stale_session_version(): void
    {
        $user = $this->administrator();
        $user->forceFill(['session_version' => 3])->save();
        $guard = Auth::guard();
        $recaller = $user->id.'|'.$user->remember_token.'|'.$guard->hashPasswordForCookie($user->password);

        $this->withCookie($guard->getRecallerName(), $recaller)
            ->withSession(['account_session_version' => 1])
            ->get(route('admin.users.index'))->assertForbidden();

        $this->assertGuest();
    }

    public function test_user_filters_and_status_payload_reject_unsupported_values(): void
    {
        $administrator = $this->administrator();
        $target = $this->administrator();

        $this->actingAs($administrator)->get(route('admin.users.index', ['status' => 'invalid', 'access' => 'invalid', 'per_page' => 100000]))
            ->assertSessionHasErrors(['status', 'access', 'per_page']);
        $this->patch(route('admin.users.status', $target), ['is_active' => 'invalid'])
            ->assertSessionHasErrors(['is_active']);

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
    }

    private function administrator(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    /**
     * @return array{name: string, email: string, is_admin: bool, is_active: bool, password: string, password_confirmation: string}
     */
    private function userPayload(): array
    {
        return [
            'name' => 'New User',
            'email' => 'new.user@example.test',
            'is_admin' => true,
            'is_active' => true,
            'password' => 'SecurePassword!123',
            'password_confirmation' => 'SecurePassword!123',
        ];
    }
}
