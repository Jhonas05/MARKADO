<?php

namespace App\Console\Commands;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Enums\StudentVerificationStatus;
use App\Gamification\Support\CharacterCatalogSynchronizer;
use App\Gamification\Support\CharacterEconomyService;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Support\HostedPresentationEnvironment;
use App\Identity\Support\HostedPresentationRegistrationEnvironment;
use App\Models\SchoolStudentRecord;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ManageHostedPresentation extends Command
{
    protected $signature = 'markado:presentation
        {action=status : provision or status}
        {--confirm= : Exact hosted presentation environment ID required for provision}';

    protected $description = 'Provision or inspect the guarded hosted MARKADO presentation environment';

    public function handle(InstitutionalEmailPolicy $emails): int
    {
        $action = strtolower(trim((string) $this->argument('action')));

        try {
            return match ($action) {
                'provision' => $this->provision($emails),
                'status' => $this->status(),
                default => throw new RuntimeException('Action must be provision or status.'),
            };
        } catch (Throwable $exception) {
            $this->components->error('Presentation operation refused or failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function provision(InstitutionalEmailPolicy $emails): int
    {
        $databaseName = $this->assertSafeRuntime(requireMarker: false);
        $environmentId = (string) config('presentation.environment_id');

        if (! hash_equals($environmentId, trim((string) $this->option('confirm')))) {
            throw new RuntimeException('Provisioning requires --confirm with the exact hosted presentation environment ID.');
        }

        $accounts = $this->validatedAccounts($emails);
        $credentialsPath = $this->credentialsRelativePath($environmentId);
        $credentialsAlreadyExist = Storage::disk('local')->exists($credentialsPath);
        $credentials = $this->credentials($environmentId, $accounts);

        if (! $credentialsAlreadyExist && User::query()
            ->whereIn(DB::raw('lower(email)'), array_map('strtolower', [
                $accounts['administrator_email'],
                $accounts['professor_email'],
                $accounts['fallback_student_email'],
            ]))->exists()) {
            throw new RuntimeException('A configured synthetic account identifier already exists without the command-owned credential file.');
        }

        // Persist the generated secrets before changing account passwords. A
        // retry then reuses the same values instead of orphaning credentials.
        $this->writeCredentials($environmentId, $credentials);

        try {
            DB::transaction(function () use (
                $accounts,
                $credentials,
                $credentialsAlreadyExist,
                $databaseName,
                $environmentId,
            ): void {
                $this->ensureGuardMarker($databaseName, $environmentId);

                $administrator = $this->upsertAccount(
                    $accounts['administrator_email'],
                    'Administrator Demo',
                    AccountRole::AcademicAdministrator,
                    $credentials['administrator']['password'],
                    $credentialsAlreadyExist,
                );
                $professor = $this->upsertAccount(
                    $accounts['professor_email'],
                    'Professor Demo',
                    AccountRole::Instructor,
                    $credentials['professor']['password'],
                    $credentialsAlreadyExist,
                );
                $student = $this->upsertAccount(
                    $accounts['fallback_student_email'],
                    'Student Demo',
                    AccountRole::Student,
                    $credentials['fallback_student']['password'],
                    $credentialsAlreadyExist,
                );

                $this->upsertFallbackRoster($student, $professor, $accounts);
                $this->upsertRegistrationRoster($professor, $accounts);

                // Keep the local variables deliberately referenced: provisioning
                // all three role paths is part of this command's contract.
                if (! $administrator->exists || ! $professor->exists || ! $student->exists) {
                    throw new RuntimeException('The synthetic presentation accounts were not persisted.');
                }
            }, 3);
        } catch (Throwable $exception) {
            if (! $credentialsAlreadyExist) {
                Storage::disk('local')->delete($credentialsPath);
            }

            throw $exception;
        }

        if (config('gamification.enabled')) {
            app(CharacterCatalogSynchronizer::class)->sync();
            $student = User::query()->where('email', $accounts['fallback_student_email'])->firstOrFail();
            app(CharacterEconomyService::class)->provisionStarterCharacter($student);
        }

        $this->components->info('The guarded hosted presentation dataset is provisioned and ready for configuration checks.');
        $this->line('Temporary credentials were written only to the private application storage path:');
        $this->line($this->credentialsAbsolutePath($environmentId));
        $this->line('No password was printed. Public registration and assessment routes remain controlled by environment flags.');

        return self::SUCCESS;
    }

    private function status(): int
    {
        $databaseName = $this->assertSafeRuntime(requireMarker: true);

        $this->table(['Check', 'Value'], [
            ['Environment', (string) config('presentation.environment_name')],
            ['Application URL', (string) config('presentation.expected_url')],
            ['Database', $databaseName],
            ['Guard marker', HostedPresentationEnvironment::enabled() ? 'verified' : 'missing'],
            ['Synthetic accounts', (string) User::query()->whereIn('role', [
                AccountRole::Student->value,
                AccountRole::Instructor->value,
                AccountRole::AcademicAdministrator->value,
            ])->count()],
            ['Synthetic roster records', (string) SchoolStudentRecord::query()
                ->where('roster_source', config('identity.trusted_roster_source'))->count()],
            ['Registration routes', HostedPresentationRegistrationEnvironment::enabled() ? 'ready' : 'gated'],
            ['Assessment routes', config('assessment.routes_enabled') ? 'enabled' : 'gated'],
        ]);

        return self::SUCCESS;
    }

    private function assertSafeRuntime(bool $requireMarker): string
    {
        if (! HostedPresentationEnvironment::configurationIsSafe()) {
            throw new RuntimeException('Hosted presentation identity, URL, Railway environment, or PostgreSQL configuration did not verify.');
        }

        foreach (['users', 'school_student_records', HostedPresentationEnvironment::GUARD_TABLE] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required migrated table [{$table}] is unavailable.");
            }
        }

        $database = DB::selectOne('select current_database() as database_name');
        $databaseName = is_string($database?->database_name) ? $database->database_name : '';
        if ($databaseName === ''
            || ! hash_equals((string) config('database.connections.pgsql.database'), $databaseName)) {
            throw new RuntimeException('The active PostgreSQL database does not match the configured database name.');
        }

        if ($requireMarker && ! HostedPresentationEnvironment::enabled()) {
            throw new RuntimeException('The hosted presentation database guard marker is missing or mismatched.');
        }

        return $databaseName;
    }

    /** @return array<string, string> */
    private function validatedAccounts(InstitutionalEmailPolicy $emails): array
    {
        $accounts = config('presentation.accounts');
        if (! is_array($accounts)) {
            throw new RuntimeException('Presentation account identifiers are not configured.');
        }

        $required = [
            'administrator_email',
            'professor_email',
            'fallback_student_email',
            'fallback_student_number',
            'registration_email',
            'registration_student_number',
        ];
        foreach ($required as $key) {
            if (! is_string($accounts[$key] ?? null) || trim($accounts[$key]) === '') {
                throw new RuntimeException("Presentation account setting [{$key}] is required.");
            }
        }

        foreach (['administrator_email', 'professor_email', 'fallback_student_email', 'registration_email'] as $key) {
            if (filter_var($accounts[$key], FILTER_VALIDATE_EMAIL) === false) {
                throw new RuntimeException("Presentation account setting [{$key}] is not a valid email address.");
            }
        }

        if ($emails->normalizeAllowedAddress($accounts['fallback_student_email']) === null
            || $emails->normalizeAllowedAddress($accounts['registration_email']) === null) {
            throw new RuntimeException('Both Student mailboxes must match the configured presentation institutional-domain policy.');
        }

        $uniqueEmails = array_map('strtolower', [
            $accounts['administrator_email'],
            $accounts['professor_email'],
            $accounts['fallback_student_email'],
            $accounts['registration_email'],
        ]);
        if (count(array_unique($uniqueEmails)) !== count($uniqueEmails)
            || hash_equals($accounts['fallback_student_number'], $accounts['registration_student_number'])) {
            throw new RuntimeException('Presentation account emails and Student numbers must be distinct.');
        }

        return $accounts;
    }

    private function upsertAccount(
        string $email,
        string $name,
        AccountRole $role,
        string $password,
        bool $credentialsAlreadyExist,
    ): User {
        $user = User::query()->whereRaw('lower(email) = ?', [strtolower($email)])->lockForUpdate()->first();
        if ($user !== null && ! $credentialsAlreadyExist) {
            throw new RuntimeException("An account already uses the configured synthetic identifier [{$email}]; it was not overwritten.");
        }
        if ($user !== null && $user->role !== $role) {
            throw new RuntimeException("The existing synthetic account [{$email}] has an unexpected role.");
        }

        $user ??= new User;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'account_status' => AccountStatus::Active,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    /** @param array<string, string> $accounts */
    private function upsertFallbackRoster(User $student, User $professor, array $accounts): void
    {
        $record = SchoolStudentRecord::query()
            ->where('student_number', $accounts['fallback_student_number'])
            ->lockForUpdate()
            ->first() ?? new SchoolStudentRecord;

        if ($record->exists && ($record->institutional_email !== $accounts['fallback_student_email']
            || ($record->user_id !== null && $record->user_id !== $student->id))) {
            throw new RuntimeException('The fallback Student roster identifier conflicts with an existing record.');
        }

        $record->forceFill([
            'user_id' => $student->id,
            'student_number' => $accounts['fallback_student_number'],
            'institutional_email' => $accounts['fallback_student_email'],
            'roster_source' => config('identity.trusted_roster_source'),
            'roster_synced_at' => now(),
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_by' => $professor->id,
            'verified_at' => now(),
            'linked_at' => $record->linked_at ?? now(),
        ])->save();
    }

    /** @param array<string, string> $accounts */
    private function upsertRegistrationRoster(User $professor, array $accounts): void
    {
        $record = SchoolStudentRecord::query()
            ->where('student_number', $accounts['registration_student_number'])
            ->lockForUpdate()
            ->first() ?? new SchoolStudentRecord;

        if ($record->exists && $record->institutional_email !== $accounts['registration_email']) {
            throw new RuntimeException('The registration Student roster identifier conflicts with an existing record.');
        }

        $record->forceFill([
            'student_number' => $accounts['registration_student_number'],
            'institutional_email' => $accounts['registration_email'],
            'roster_source' => config('identity.trusted_roster_source'),
            'roster_synced_at' => now(),
            'verification_status' => StudentVerificationStatus::Verified,
            'is_active' => true,
            'verified_by' => $professor->id,
            'verified_at' => now(),
        ])->save();
    }

    private function ensureGuardMarker(string $databaseName, string $environmentId): void
    {
        $existing = DB::table(HostedPresentationEnvironment::GUARD_TABLE)->lockForUpdate()->get();
        if ($existing->isNotEmpty()) {
            $matches = $existing->count() === 1
                && hash_equals((string) $existing->first()->guard_key, HostedPresentationEnvironment::GUARD_KEY)
                && hash_equals((string) $existing->first()->environment_id, $environmentId)
                && hash_equals((string) $existing->first()->environment_name, (string) config('presentation.environment_name'))
                && hash_equals((string) $existing->first()->app_url, (string) config('presentation.expected_url'))
                && hash_equals((string) $existing->first()->database_name, $databaseName);
            if (! $matches) {
                throw new RuntimeException('The database contains a different hosted-presentation guard marker.');
            }

            return;
        }

        DB::table(HostedPresentationEnvironment::GUARD_TABLE)->insert([
            'guard_key' => HostedPresentationEnvironment::GUARD_KEY,
            'environment_id' => $environmentId,
            'environment_name' => config('presentation.environment_name'),
            'app_url' => config('presentation.expected_url'),
            'database_name' => $databaseName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, string> $accounts */
    private function credentials(string $environmentId, array $accounts): array
    {
        $path = $this->credentialsRelativePath($environmentId);
        if (Storage::disk('local')->exists($path)) {
            $credentials = json_decode((string) Storage::disk('local')->get($path), true);
            if (! is_array($credentials)
                || ($credentials['environment_id'] ?? null) !== $environmentId
                || ($credentials['administrator']['email'] ?? null) !== $accounts['administrator_email']
                || ($credentials['professor']['email'] ?? null) !== $accounts['professor_email']
                || ($credentials['fallback_student']['email'] ?? null) !== $accounts['fallback_student_email']
                || ! is_string($credentials['administrator']['password'] ?? null)
                || ! is_string($credentials['professor']['password'] ?? null)
                || ! is_string($credentials['fallback_student']['password'] ?? null)) {
                throw new RuntimeException('The existing private presentation credential file is invalid; it was not overwritten.');
            }

            return $credentials;
        }

        return [
            'environment_id' => $environmentId,
            'administrator' => [
                'email' => $accounts['administrator_email'],
                'password' => Str::password(24),
            ],
            'professor' => [
                'email' => $accounts['professor_email'],
                'password' => Str::password(24),
            ],
            'fallback_student' => [
                'email' => $accounts['fallback_student_email'],
                'student_number' => $accounts['fallback_student_number'],
                'password' => Str::password(24),
            ],
            'registration_student' => [
                'email' => $accounts['registration_email'],
                'student_number' => $accounts['registration_student_number'],
            ],
        ];
    }

    private function writeCredentials(string $environmentId, array $credentials): void
    {
        $written = Storage::disk('local')->put(
            $this->credentialsRelativePath($environmentId),
            json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
        if (! $written) {
            throw new RuntimeException('The private presentation credential file could not be written.');
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            @chmod($this->credentialsAbsolutePath($environmentId), 0600);
        }
    }

    private function credentialsRelativePath(string $environmentId): string
    {
        return "markado-presentation/{$environmentId}/credentials.json";
    }

    private function credentialsAbsolutePath(string $environmentId): string
    {
        return Storage::disk('local')->path($this->credentialsRelativePath($environmentId));
    }
}
