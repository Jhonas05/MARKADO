<?php

namespace Tests\Feature;

use App\Identity\Support\HostedPresentationEnvironment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('postgresql')]
class PostgreSqlHostedPresentationCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Requires the dedicated PostgreSQL test environment.');
        }

        Storage::fake('local');
        $this->app['env'] = 'staging';
        config()->set([
            'app.url' => 'https://markado-presentation.example.test',
            'presentation.hosted_enabled' => true,
            'presentation.expected_url' => 'https://markado-presentation.example.test',
            'presentation.production_url' => 'https://markado-production.example.test',
            'presentation.environment_id' => 'presentation-test-environment',
            'presentation.environment_name' => 'presentation-test',
            'presentation.runtime_environment_id' => 'presentation-test-environment',
            'presentation.runtime_environment_name' => 'presentation-test',
            'presentation.accounts' => [
                'administrator_email' => 'administrator.presentation@example.test',
                'professor_email' => 'professor.presentation@example.test',
                'fallback_student_email' => 'fallback.student@example.test',
                'fallback_student_number' => 'PRESENT-0001',
                'registration_email' => 'register.student@example.test',
                'registration_student_number' => 'PRESENT-0002',
            ],
            'identity.institutional_email_domains' => ['example.test'],
            'identity.trusted_roster_source' => 'synthetic-presentation-roster',
            'identity.trusted_roster_max_age_hours' => 24,
        ]);
    }

    public function test_command_idempotently_provisions_guarded_synthetic_roles_and_roster(): void
    {
        $this->artisan('markado:presentation provision --confirm=presentation-test-environment')
            ->expectsOutputToContain('hosted presentation dataset is provisioned')
            ->assertSuccessful();

        $credentialsPath = 'markado-presentation/presentation-test-environment/credentials.json';
        Storage::disk('local')->assertExists($credentialsPath);
        $credentials = json_decode(Storage::disk('local')->get($credentialsPath), true, flags: JSON_THROW_ON_ERROR);

        $this->assertDatabaseHas(HostedPresentationEnvironment::GUARD_TABLE, [
            'guard_key' => HostedPresentationEnvironment::GUARD_KEY,
            'environment_id' => 'presentation-test-environment',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'administrator.presentation@example.test',
            'role' => 'academic_administrator',
            'account_status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'professor.presentation@example.test',
            'role' => 'instructor',
            'account_status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'fallback.student@example.test',
            'role' => 'student',
            'account_status' => 'active',
        ]);

        $fallback = DB::table('users')->where('email', 'fallback.student@example.test')->first();
        $this->assertTrue(Hash::check($credentials['fallback_student']['password'], $fallback->password));
        $this->assertDatabaseHas('school_student_records', [
            'student_number' => 'PRESENT-0001',
            'institutional_email' => 'fallback.student@example.test',
            'user_id' => $fallback->id,
            'roster_source' => 'synthetic-presentation-roster',
            'verification_status' => 'verified',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('school_student_records', [
            'student_number' => 'PRESENT-0002',
            'institutional_email' => 'register.student@example.test',
            'user_id' => null,
            'roster_source' => 'synthetic-presentation-roster',
            'verification_status' => 'verified',
            'is_active' => true,
        ]);

        $this->assertTrue(HostedPresentationEnvironment::enabled());

        $this->artisan('markado:presentation provision --confirm=presentation-test-environment')
            ->assertSuccessful();
        $this->assertSame(3, DB::table('users')->count());
        $this->assertSame(2, DB::table('school_student_records')->count());
        $this->assertSame(1, DB::table(HostedPresentationEnvironment::GUARD_TABLE)->count());
    }

    public function test_command_refuses_wrong_confirmation_without_writing_data(): void
    {
        $this->artisan('markado:presentation provision --confirm=production')
            ->expectsOutputToContain('exact hosted presentation environment ID')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount(HostedPresentationEnvironment::GUARD_TABLE, 0);
        Storage::disk('local')->assertMissing('markado-presentation/presentation-test-environment/credentials.json');
    }
}
