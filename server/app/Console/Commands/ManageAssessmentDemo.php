<?php

namespace App\Console\Commands;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Models\Quiz;
use App\Models\QuizStudentAssignment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ManageAssessmentDemo extends Command
{
    private string $databaseName = '';

    private const GUARD_KEY = 'markado-v8-9-functional-assessment-demo';

    private const GUARD_TABLE = 'markado_demo_environment_guard';

    private const PROFESSOR_EMAIL = 'professor.demo@example.test';

    private const STUDENT_EMAIL = 'student.demo@students.example.edu';

    private const STUDENT_NUMBER = 'DEMO-V89-0001';

    protected $signature = 'markado:demo
        {action=provision : provision, assign, status, or cleanup}
        {--schema= : Dedicated schema named markado_demo_v89_*}
        {--quiz-code= : Professor-owned quiz code to assign to the demo Student}
        {--confirm= : Exact schema name required for cleanup}';

    protected $description = 'Manage the guarded, synthetic PostgreSQL assessment demo environment';

    public function handle(): int
    {
        $action = strtolower(trim((string) $this->argument('action')));
        $schema = strtolower(trim((string) $this->option('schema')));
        $originalSearchPath = config('database.connections.pgsql.search_path');

        try {
            $this->assertSafeRuntime($schema);

            return match ($action) {
                'provision' => $this->provision($schema),
                'assign' => $this->assign($schema),
                'status' => $this->status($schema),
                'cleanup' => $this->cleanup($schema),
                default => throw new RuntimeException('Action must be provision, assign, status, or cleanup.'),
            };
        } catch (Throwable $exception) {
            $this->components->error('Demo operation refused or failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            $this->restoreSearchPath($originalSearchPath);
        }
    }

    private function provision(string $schema): int
    {
        if ($this->schemaExists($schema)) {
            $this->useSchema($schema);
            if (! $this->hasGuardMarker($schema)) {
                throw new RuntimeException('The existing schema is not owned by the MARKADO v8.9 demo command.');
            }
        } else {
            $this->useControlSchema();
            $this->createSchema($schema);
            $this->useSchema($schema);
            $this->ensureGuardMarker($schema);
        }

        if (Artisan::call('migrate', [
            '--database' => 'pgsql',
            '--force' => true,
        ]) !== self::SUCCESS) {
            throw new RuntimeException('Migrations did not complete in the isolated demo schema.');
        }

        $this->assertCurrentSchema($schema);
        $credentials = $this->credentials($schema);

        DB::transaction(function () use ($credentials): void {
            $professor = User::query()->firstOrNew(['email' => self::PROFESSOR_EMAIL]);
            $professor->forceFill([
                'name' => 'Professor Demo',
                'password' => $credentials['professor']['password'],
                'role' => AccountRole::Instructor,
                'account_status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ])->save();

            $student = User::query()->firstOrNew(['email' => self::STUDENT_EMAIL]);
            $student->forceFill([
                'name' => 'Student Demo',
                'password' => $credentials['student']['password'],
                'role' => AccountRole::Student,
                'account_status' => AccountStatus::Active,
                'email_verified_at' => now(),
            ])->save();

            $record = SchoolStudentRecord::query()
                ->where('student_number', self::STUDENT_NUMBER)
                ->first() ?? new SchoolStudentRecord;

            if ($record->exists && $record->user_id !== null && $record->user_id !== $student->id) {
                throw new RuntimeException('The synthetic roster identity is already linked to another account.');
            }

            $record->forceFill([
                'user_id' => $student->id,
                'student_number' => self::STUDENT_NUMBER,
                'institutional_email' => self::STUDENT_EMAIL,
                'verification_status' => StudentVerificationStatus::Verified,
                'is_active' => true,
                'verified_by' => $professor->id,
                'verified_at' => now(),
                'linked_at' => now(),
            ])->save();
        });

        $this->writeCredentials($schema, $credentials);
        $this->components->info("Loopback database [{$this->databaseName}] and isolated demo schema [{$schema}] are migrated and provisioned.");
        $this->line('Credentials were written only to the ignored private file:');
        $this->line($this->credentialsAbsolutePath($schema));
        $this->line('No Administrator account was created. Assessment routes and expiration remain controlled by process environment.');

        if (filled($this->option('quiz-code'))) {
            return $this->assignCurrentSchema((string) $this->option('quiz-code'));
        }

        return self::SUCCESS;
    }

    private function assign(string $schema): int
    {
        $this->useOwnedSchema($schema);

        return $this->assignCurrentSchema((string) $this->option('quiz-code'));
    }

    private function assignCurrentSchema(string $quizCode): int
    {
        $code = strtoupper(trim($quizCode));
        if (! preg_match('/\A[A-HJ-NP-Z2-9]{8}\z/', $code)) {
            throw new RuntimeException('A valid eight-character --quiz-code is required for assignment.');
        }

        DB::transaction(function () use ($code): void {
            $professor = User::query()->where('email', self::PROFESSOR_EMAIL)->first();
            $student = User::query()->where('email', self::STUDENT_EMAIL)->first();
            $record = $student?->schoolStudentRecord;

            if ($professor === null || $student === null || $record === null) {
                throw new RuntimeException('Provision the demo identities before assigning a quiz.');
            }

            if ($professor->role !== AccountRole::Instructor
                || $professor->account_status !== AccountStatus::Active
                || $student->role !== AccountRole::Student
                || $student->account_status !== AccountStatus::Active
                || $student->email_verified_at === null
                || $record->verification_status !== StudentVerificationStatus::Verified
                || $record->verified_at === null
                || ! $record->is_active) {
                throw new RuntimeException('The synthetic identities no longer satisfy the guarded demo requirements.');
            }

            $quiz = Quiz::query()->where('access_code', $code)->lockForUpdate()->first();
            if ($quiz === null || $quiz->owner_id !== $professor->id) {
                throw new RuntimeException('The code does not identify a quiz owned by the synthetic Professor.');
            }

            $assignment = QuizStudentAssignment::query()
                ->where('quiz_id', $quiz->id)
                ->where('student_record_id', $record->id)
                ->first() ?? new QuizStudentAssignment;
            $assignment->quiz()->associate($quiz);
            $assignment->studentRecord()->associate($record);
            $assignment->is_active = true;
            $assignment->assigned_by = $professor->id;
            $assignment->save();
        });

        $this->components->info("The synthetic Student is assigned to quiz [{$code}].");

        return self::SUCCESS;
    }

    private function status(string $schema): int
    {
        $this->useOwnedSchema($schema);

        $professor = User::query()->where('email', self::PROFESSOR_EMAIL)->first();
        $student = User::query()->where('email', self::STUDENT_EMAIL)->first();

        $this->table(['Check', 'Value'], [
            ['Schema', $schema],
            ['Professor ready', $professor?->role === AccountRole::Instructor && $professor?->account_status === AccountStatus::Active ? 'yes' : 'no'],
            ['Student ready', $student?->role === AccountRole::Student && $student?->account_status === AccountStatus::Active ? 'yes' : 'no'],
            ['Quiz assignments', (string) QuizStudentAssignment::query()->where('is_active', true)->count()],
            ['Attempts', (string) DB::table('quiz_attempts')->count()],
        ]);

        return self::SUCCESS;
    }

    private function cleanup(string $schema): int
    {
        if (! hash_equals($schema, strtolower(trim((string) $this->option('confirm'))))) {
            throw new RuntimeException('Cleanup requires --confirm with the exact isolated schema name.');
        }

        $this->useOwnedSchema($schema);
        DB::statement("DROP SCHEMA \"{$schema}\" CASCADE");
        DB::purge('pgsql');
        Storage::disk('local')->deleteDirectory("markado-demo/{$schema}");
        if (Storage::disk('local')->exists("markado-demo/{$schema}")) {
            throw new RuntimeException('The demo schema was removed, but its private credential directory could not be deleted.');
        }
        $this->components->info("Removed only the guarded demo schema [{$schema}] and its private credential file.");

        return self::SUCCESS;
    }

    private function assertSafeRuntime(string $schema): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The assessment demo command is restricted to local or automated test environments.');
        }

        if (! preg_match('/\Amarkado_demo_v89_[a-z0-9_]+\z/', $schema)) {
            throw new RuntimeException('Use a dedicated schema named markado_demo_v89_*; public and other schemas are refused.');
        }

        if (config('database.default') !== 'pgsql') {
            throw new RuntimeException('The assessment demo requires the PostgreSQL connection.');
        }

        $server = DB::connection('pgsql')->selectOne(<<<'SQL'
            SELECT current_database() AS database_name, inet_server_addr()::text AS server_address
            SQL);
        $address = is_string($server?->server_address)
            ? explode('/', $server->server_address, 2)[0]
            : null;
        $databaseName = is_string($server?->database_name) ? trim($server->database_name) : '';
        $configuredHost = strtolower(trim((string) config('database.connections.pgsql.host')));
        $configuredLoopback = in_array($configuredHost, ['127.0.0.1', 'localhost', '::1'], true);
        $containerizedCiDatabase = app()->environment('testing') && $databaseName === 'markado_ci';

        if (! $configuredLoopback
            || (! in_array($address, ['127.0.0.1', '::1'], true) && ! $containerizedCiDatabase)) {
            throw new RuntimeException('The PostgreSQL server did not verify as a loopback-only connection.');
        }

        if ($databaseName === '' || $databaseName !== (string) config('database.connections.pgsql.database')) {
            throw new RuntimeException('The active PostgreSQL database does not match the configured database name.');
        }

        $this->databaseName = $databaseName;
    }

    private function createSchema(string $schema): void
    {
        DB::statement("CREATE SCHEMA \"{$schema}\"");
    }

    private function useOwnedSchema(string $schema): void
    {
        if (! $this->schemaExists($schema)) {
            throw new RuntimeException('The requested demo schema does not exist.');
        }

        $this->useSchema($schema);

        if (! $this->hasGuardMarker($schema)) {
            throw new RuntimeException('The schema is not owned by the MARKADO v8.9 demo command.');
        }
    }

    private function schemaExists(string $schema): bool
    {
        $exists = DB::selectOne(
            'SELECT EXISTS (SELECT 1 FROM pg_namespace WHERE nspname = ?) AS present',
            [$schema],
        );

        return filter_var($exists?->present, FILTER_VALIDATE_BOOL);
    }

    private function useSchema(string $schema): void
    {
        config()->set('database.connections.pgsql.search_path', $schema);
        DB::purge('pgsql');
        DB::reconnect('pgsql');
        $this->assertCurrentSchema($schema);
    }

    private function useControlSchema(): void
    {
        config()->set('database.connections.pgsql.search_path', 'pg_catalog');
        DB::purge('pgsql');
        DB::reconnect('pgsql');

        $current = DB::connection('pgsql')->selectOne('SELECT current_schema() AS schema_name');
        if ($current?->schema_name !== 'pg_catalog') {
            throw new RuntimeException('PostgreSQL did not select the non-user-data control schema.');
        }
    }

    private function assertCurrentSchema(string $schema): void
    {
        $current = DB::connection('pgsql')->selectOne('SELECT current_schema() AS schema_name');
        if ($current?->schema_name !== $schema) {
            throw new RuntimeException('PostgreSQL did not select the isolated demo schema.');
        }
    }

    private function ensureGuardMarker(string $schema): void
    {
        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS markado_demo_environment_guard (
                guard_key varchar(64) PRIMARY KEY,
                schema_name varchar(63) NOT NULL,
                created_at timestamptz NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
            SQL);
        DB::table(self::GUARD_TABLE)->updateOrInsert(
            ['guard_key' => self::GUARD_KEY],
            ['schema_name' => $schema, 'created_at' => now()],
        );
    }

    private function hasGuardMarker(string $schema): bool
    {
        $table = DB::selectOne('SELECT to_regclass(?) AS table_name', [self::GUARD_TABLE]);
        if ($table?->table_name === null) {
            return false;
        }

        return DB::table(self::GUARD_TABLE)
            ->where('guard_key', self::GUARD_KEY)
            ->where('schema_name', $schema)
            ->exists();
    }

    private function credentials(string $schema): array
    {
        $path = $this->credentialsRelativePath($schema);
        if (Storage::disk('local')->exists($path)) {
            $credentials = json_decode((string) Storage::disk('local')->get($path), true);
            if (! is_array($credentials)
                || ($credentials['schema'] ?? null) !== $schema
                || ($credentials['professor']['email'] ?? null) !== self::PROFESSOR_EMAIL
                || ($credentials['student']['email'] ?? null) !== self::STUDENT_EMAIL
                || ($credentials['student']['student_number'] ?? null) !== self::STUDENT_NUMBER
                || ! is_string($credentials['professor']['password'] ?? null)
                || ! is_string($credentials['student']['password'] ?? null)) {
                throw new RuntimeException('The existing private credential file is invalid; it was not overwritten.');
            }

            return $credentials;
        }

        return [
            'schema' => $schema,
            'professor' => [
                'name' => 'Professor Demo',
                'email' => self::PROFESSOR_EMAIL,
                'password' => Str::password(24),
            ],
            'student' => [
                'name' => 'Student Demo',
                'email' => self::STUDENT_EMAIL,
                'student_number' => self::STUDENT_NUMBER,
                'password' => Str::password(24),
            ],
        ];
    }

    private function writeCredentials(string $schema, array $credentials): void
    {
        $path = $this->credentialsRelativePath($schema);
        $written = Storage::disk('local')->put(
            $path,
            json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        if (! $written) {
            throw new RuntimeException('The private credential file could not be written.');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod($this->credentialsAbsolutePath($schema), 0600);
        }
    }

    private function credentialsRelativePath(string $schema): string
    {
        return "markado-demo/{$schema}/credentials.json";
    }

    private function credentialsAbsolutePath(string $schema): string
    {
        return Storage::disk('local')->path($this->credentialsRelativePath($schema));
    }

    private function restoreSearchPath(mixed $searchPath): void
    {
        if (config('database.default') !== 'pgsql') {
            return;
        }

        config()->set('database.connections.pgsql.search_path', $searchPath);
        DB::purge('pgsql');
    }
}
