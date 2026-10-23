<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use RuntimeException;
use Tests\TestCase;

#[Group('postgresql')]
class PostgreSqlFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Requires the dedicated PostgreSQL test environment.');
        }
    }

    public function test_real_postgresql_connection_and_baseline_migrations(): void
    {
        $pdo = DB::connection()->getPdo();
        $database = DB::selectOne('select current_database() as database_name');
        $timezone = DB::selectOne("select current_setting('TIMEZONE') as timezone");

        $this->assertSame('pgsql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame(config('database.connections.pgsql.database'), $database->database_name);
        $this->assertSame('UTC', $timezone->timezone);
        $this->assertTrue(Schema::hasColumns('users', ['id', 'email', 'password', 'role']));
        $this->assertTrue(Schema::hasTable('sessions'));
        $this->assertTrue(Schema::hasTable('jobs'));
    }

    public function test_postgresql_transaction_rolls_back_as_a_unit(): void
    {
        $email = 'rollback-'.Str::uuid().'@example.test';

        try {
            DB::transaction(function () use ($email): void {
                User::factory()->create(['email' => $email]);
                throw new RuntimeException('Intentional rollback assertion.');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Intentional rollback assertion.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}
