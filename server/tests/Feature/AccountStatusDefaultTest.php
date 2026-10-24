<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountStatusDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_defaults_unclassified_accounts_to_pending(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Synthetic Pending Account',
            'email' => 'pending-default@example.test',
            'password' => 'not-a-real-credential',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            AccountStatus::Pending,
            User::query()->findOrFail($userId)->account_status,
        );
    }

    public function test_test_factory_explicitly_creates_active_usable_accounts(): void
    {
        $this->assertSame(
            AccountStatus::Active,
            User::factory()->create()->account_status,
        );
    }

    public function test_default_change_does_not_rewrite_existing_account_statuses(): void
    {
        $users = collect(AccountStatus::cases())->mapWithKeys(function (AccountStatus $status): array {
            $user = User::factory()->create(['account_status' => $status]);

            return [$user->id => $status];
        });

        $migration = require database_path('migrations/2026_10_24_120000_default_new_accounts_to_pending.php');
        $migration->down();
        $migration->up();

        $users->each(function (AccountStatus $expectedStatus, int $userId): void {
            $this->assertSame($expectedStatus, User::query()->findOrFail($userId)->account_status);
        });
    }
}
