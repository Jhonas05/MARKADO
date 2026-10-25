<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('postgresql')]
class PostgreSqlAssessmentDemoCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Requires the dedicated PostgreSQL test environment.');
        }
    }

    public function test_demo_command_provisions_assigns_and_cleans_only_its_guarded_schema(): void
    {
        $schema = 'markado_demo_v89_test_'.strtolower(Str::random(8));
        $credentialsPath = "markado-demo/{$schema}/credentials.json";
        $validationConnection = 'demo_validation_'.strtolower(Str::random(8));

        try {
            $this->artisan("markado:demo provision --schema={$schema}")
                ->expectsOutputToContain("isolated demo schema [{$schema}] are migrated and provisioned")
                ->assertSuccessful();

            $this->assertTrue(Storage::disk('local')->exists($credentialsPath));
            $credentials = json_decode((string) Storage::disk('local')->get($credentialsPath), true, flags: JSON_THROW_ON_ERROR);
            $connection = config('database.connections.pgsql');
            $connection['search_path'] = $schema;
            config()->set("database.connections.{$validationConnection}", $connection);

            $professor = DB::connection($validationConnection)->table('users')
                ->where('email', $credentials['professor']['email'])
                ->first();
            $student = DB::connection($validationConnection)->table('users')
                ->where('email', $credentials['student']['email'])
                ->first();
            $this->assertNotNull($professor);
            $this->assertNotNull($student);
            $this->assertSame('instructor', $professor->role);
            $this->assertSame('student', $student->role);
            $this->assertSame('active', $professor->account_status);
            $this->assertSame('active', $student->account_status);
            $this->assertTrue(Hash::check($credentials['professor']['password'], $professor->password));
            $this->assertTrue(Hash::check($credentials['student']['password'], $student->password));
            $this->assertSame(0, DB::connection($validationConnection)->table('users')
                ->where('role', 'academic_administrator')->count());

            $roster = DB::connection($validationConnection)->table('school_student_records')
                ->where('user_id', $student->id)
                ->first();
            $this->assertNotNull($roster);
            $this->assertSame('verified', $roster->verification_status);
            $this->assertTrue((bool) $roster->is_active);
            $this->assertNotNull($roster->verified_at);
            $this->assertNotNull($roster->linked_at);

            $quizId = (string) Str::uuid();
            DB::connection($validationConnection)->table('quizzes')->insert([
                'id' => $quizId,
                'owner_id' => $professor->id,
                'title' => 'Command assignment test',
                'course_code' => 'DEMO 101',
                'status' => 'draft',
                'access_code' => 'V89DEMA2',
                'display_timezone' => 'Asia/Manila',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->artisan("markado:demo assign --schema={$schema} --quiz-code=V89DEMA2")
                ->expectsOutputToContain('synthetic Student is assigned')
                ->assertSuccessful();
            $this->assertSame(1, DB::connection($validationConnection)->table('quiz_student_assignments')
                ->where('quiz_id', $quizId)
                ->where('student_record_id', $roster->id)
                ->where('is_active', true)
                ->count());

            $this->artisan("markado:demo cleanup --schema={$schema} --confirm=wrong_schema")
                ->expectsOutputToContain('exact isolated schema name')
                ->assertFailed();
            $this->assertTrue($this->schemaExists($schema));

            DB::purge($validationConnection);
            $this->artisan("markado:demo cleanup --schema={$schema} --confirm={$schema}")
                ->expectsOutputToContain('Removed only the guarded demo schema')
                ->assertSuccessful();
            $this->assertFalse($this->schemaExists($schema));
            $this->assertFalse(Storage::disk('local')->exists($credentialsPath));
        } finally {
            DB::purge($validationConnection);
            if ($this->schemaExists($schema)) {
                DB::statement("DROP SCHEMA \"{$schema}\" CASCADE");
            }
            Storage::disk('local')->deleteDirectory("markado-demo/{$schema}");
        }
    }

    public function test_provision_refuses_to_adopt_an_existing_unowned_schema(): void
    {
        $schema = 'markado_demo_v89_unowned_'.strtolower(Str::random(8));

        try {
            DB::statement("CREATE SCHEMA \"{$schema}\"");

            $this->artisan("markado:demo provision --schema={$schema}")
                ->expectsOutputToContain('existing schema is not owned')
                ->assertFailed();

            $this->assertTrue($this->schemaExists($schema));
            $guard = DB::selectOne('SELECT to_regclass(?) AS table_name', ["{$schema}.markado_demo_environment_guard"]);
            $users = DB::selectOne('SELECT to_regclass(?) AS table_name', ["{$schema}.users"]);
            $this->assertNull($guard?->table_name);
            $this->assertNull($users?->table_name);
        } finally {
            if ($this->schemaExists($schema)) {
                DB::statement("DROP SCHEMA \"{$schema}\" CASCADE");
            }
        }
    }

    private function schemaExists(string $schema): bool
    {
        $result = DB::connection('pgsql')->selectOne(
            'SELECT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = ?) AS present',
            [$schema],
        );

        return filter_var($result?->present, FILTER_VALIDATE_BOOL);
    }
}
