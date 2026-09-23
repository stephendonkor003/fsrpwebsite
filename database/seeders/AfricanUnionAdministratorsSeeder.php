<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AfricanUnionAdministratorsSeeder extends Seeder
{
    /**
     * @var list<array{name: string, email: string}>
     */
    private const ADMINISTRATORS = [
        [
            'name' => 'Stephen',
            'email' => 'donkors@africanunion.org',
        ],
        [
            'name' => 'Linda',
            'email' => 'makauc@africanunion.org',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('administrator_accounts.password');

        if (! is_string($password) || trim($password) === '') {
            throw new RuntimeException(
                'AFRICAN_UNION_ADMIN_PASSWORD must be supplied when running AfricanUnionAdministratorsSeeder.',
            );
        }

        DB::transaction(function () use ($password): void {
            foreach (self::ADMINISTRATORS as $administrator) {
                $this->provisionAdministrator($administrator, $password);
            }
        });
    }

    /**
     * @param  array{name: string, email: string}  $administrator
     */
    private function provisionAdministrator(array $administrator, string $password): void
    {
        $email = Str::lower(trim($administrator['email']));
        $matches = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->lockForUpdate()
            ->get();

        if ($matches->count() > 1) {
            throw new RuntimeException(
                "Multiple user accounts match {$email}. Resolve the duplicate accounts before provisioning administrators.",
            );
        }

        /** @var User|null $existingUser */
        $existingUser = $matches->first();

        if ($existingUser !== null) {
            $isExactAccount = $existingUser->email === $email
                && $existingUser->name === $administrator['name']
                && $existingUser->is_admin
                && $existingUser->is_active;

            if (! $isExactAccount) {
                throw new RuntimeException(
                    "An existing user account conflicts with {$email}. Review it before provisioning administrators.",
                );
            }

            if (! Hash::check($password, (string) $existingUser->password)) {
                throw new RuntimeException(
                    "The existing administrator {$email} uses different credentials. Refusing to reset its password.",
                );
            }

            return;
        }

        User::query()->create([
            'name' => $administrator['name'],
            'email' => $email,
            'email_verified_at' => now(),
            'password' => $password,
            'is_admin' => true,
            'is_active' => true,
        ]);
    }
}
