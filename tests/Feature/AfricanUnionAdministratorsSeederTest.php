<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AfricanUnionAdministratorsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AfricanUnionAdministratorsSeederTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'test-shared-administrator-password';

    public function test_creates_the_requested_administrators_and_they_can_log_in_case_insensitively(): void
    {
        $unrelatedUser = User::factory()->create([
            'email' => 'unrelated@example.test',
            'password' => 'unrelated-password',
            'is_admin' => false,
            'is_active' => true,
        ]);
        $unrelatedPasswordHash = $unrelatedUser->getRawOriginal('password');
        config()->set('administrator_accounts.password', self::PASSWORD);

        $this->seed(AfricanUnionAdministratorsSeeder::class);

        $stephen = User::query()->where('email', 'donkors@africanunion.org')->sole();
        $linda = User::query()->where('email', 'makauc@africanunion.org')->sole();

        $this->assertSame('Stephen', $stephen->name);
        $this->assertSame('Linda', $linda->name);
        $this->assertTrue($stephen->is_admin);
        $this->assertTrue($linda->is_admin);
        $this->assertTrue($stephen->is_active);
        $this->assertTrue($linda->is_active);
        $this->assertNotNull($stephen->email_verified_at);
        $this->assertNotNull($linda->email_verified_at);
        $this->assertTrue(Hash::check(self::PASSWORD, (string) $stephen->password));
        $this->assertTrue(Hash::check(self::PASSWORD, (string) $linda->password));
        $this->assertNotSame(self::PASSWORD, $stephen->getRawOriginal('password'));
        $this->assertNotSame(self::PASSWORD, $linda->getRawOriginal('password'));
        $this->assertSame($unrelatedPasswordHash, $unrelatedUser->fresh()->getRawOriginal('password'));
        $this->assertFalse($unrelatedUser->fresh()->is_admin);
        $this->assertDatabaseCount('users', 3);

        foreach ([
            ['email' => 'DonkorS@AfricanUnion.org', 'user' => $stephen],
            ['email' => 'MakauC@AfricanUnion.org', 'user' => $linda],
        ] as $credentials) {
            $this->post(route('login.store'), [
                'email' => $credentials['email'],
                'password' => self::PASSWORD,
            ])->assertRedirect(route('admin.dashboard'));

            $this->assertAuthenticatedAs($credentials['user']);
            $this->post(route('admin.logout'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }

    public function test_rerunning_is_stable_and_refuses_to_reset_a_changed_password(): void
    {
        config()->set('administrator_accounts.password', self::PASSWORD);
        $this->seed(AfricanUnionAdministratorsSeeder::class);

        $stephen = User::query()->where('email', 'donkors@africanunion.org')->sole();
        $linda = User::query()->where('email', 'makauc@africanunion.org')->sole();
        $originalIds = [$stephen->id, $linda->id];
        $originalHashes = [$stephen->getRawOriginal('password'), $linda->getRawOriginal('password')];

        $this->seed(AfricanUnionAdministratorsSeeder::class);

        $this->assertSame($originalIds, User::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame($originalHashes, User::query()->orderBy('id')->pluck('password')->all());

        $stephen->update([
            'password' => 'changed-after-provisioning',
            'last_login_at' => now(),
        ]);
        $changedPasswordHash = $stephen->fresh()->getRawOriginal('password');

        try {
            $this->seed(AfricanUnionAdministratorsSeeder::class);
            $this->fail('A rerun should not reset an administrator password that has changed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Refusing to reset its password', $exception->getMessage());
        }

        $stephen->refresh();
        $linda->refresh();
        $this->assertSame($originalIds, [$stephen->id, $linda->id]);
        $this->assertSame($changedPasswordHash, $stephen->getRawOriginal('password'));
        $this->assertSame($originalHashes[1], $linda->getRawOriginal('password'));
        $this->assertNotNull($stephen->last_login_at);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_missing_password_stops_before_creating_accounts(): void
    {
        config()->set('administrator_accounts.password', null);

        try {
            $this->seed(AfricanUnionAdministratorsSeeder::class);
            $this->fail('A missing administrator password should stop the seeder.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('AFRICAN_UNION_ADMIN_PASSWORD', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_case_insensitive_duplicate_accounts_stop_the_entire_seed(): void
    {
        User::factory()->create(['email' => 'makauc@africanunion.org']);
        User::factory()->create(['email' => 'MakauC@africanunion.org']);
        config()->set('administrator_accounts.password', self::PASSWORD);

        try {
            $this->seed(AfricanUnionAdministratorsSeeder::class);
            $this->fail('Ambiguous email accounts should stop the seeder.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Multiple user accounts match makauc@africanunion.org', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'donkors@africanunion.org']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_non_administrator_email_collision_stops_the_entire_seed(): void
    {
        $existingUser = User::factory()->create([
            'name' => 'Linda',
            'email' => 'makauc@africanunion.org',
            'password' => self::PASSWORD,
            'is_admin' => false,
            'is_active' => true,
        ]);
        config()->set('administrator_accounts.password', self::PASSWORD);

        try {
            $this->seed(AfricanUnionAdministratorsSeeder::class);
            $this->fail('A non-administrator email collision should stop the seeder.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('conflicts with makauc@africanunion.org', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => 'donkors@africanunion.org']);
        $this->assertFalse($existingUser->fresh()->is_admin);
        $this->assertDatabaseCount('users', 1);
    }
}
