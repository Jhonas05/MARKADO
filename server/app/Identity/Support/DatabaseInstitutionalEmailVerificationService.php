<?php

namespace App\Identity\Support;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Identity\Contracts\InstitutionalEmailPolicy;
use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Identity\Contracts\InstitutionalVerificationAbuseGate;
use App\Identity\Contracts\InstitutionalVerificationClock;
use App\Identity\Data\InstitutionalEmailVerificationMessage;
use App\Identity\Data\InstitutionalEmailVerificationResult;
use App\Models\InstitutionalEmailVerificationChallenge;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DatabaseInstitutionalEmailVerificationService implements InstitutionalEmailVerificationService
{
    private const PURPOSE = 'institutional_mailbox';

    public function __construct(
        private readonly InstitutionalEmailPolicy $emailPolicy,
        private readonly InstitutionalEmailVerificationDelivery $delivery,
        private readonly InstitutionalVerificationAbuseGate $abuseGate,
        private readonly InstitutionalVerificationClock $clock,
        private readonly IdentityAccessEventRecorder $events,
    ) {}

    public function issue(User $student, ?string $networkContext = null): InstitutionalEmailVerificationResult
    {
        if (! config('identity.institutional_email_verification_enabled')
            || ! config('identity.institutional_email_verification_delivery_enabled')) {
            return InstitutionalEmailVerificationResult::denied('institutional_verification_disabled');
        }

        $hourlyLimit = config('identity.institutional_email_verification_hourly_limit');
        if (! is_int($hourlyLimit) || $hourlyLimit < 1) {
            return InstitutionalEmailVerificationResult::denied('verification_hourly_policy_unavailable');
        }

        $abuseDecision = $this->abuseGate->evaluate($student, $networkContext);
        if (! $abuseDecision->matched) {
            return InstitutionalEmailVerificationResult::denied($abuseDecision->reason);
        }

        $now = $this->clock->now();
        $prepared = DB::transaction(function () use ($student, $hourlyLimit, $now): array|InstitutionalEmailVerificationResult {
            $lockedStudent = User::query()->lockForUpdate()->find($student->getKey());
            $email = $lockedStudent === null || ! is_string($lockedStudent->email)
                ? null
                : $this->emailPolicy->normalizeAllowedAddress($lockedStudent->email);

            if ($lockedStudent === null
                || $lockedStudent->role !== AccountRole::Student
                || $lockedStudent->account_status === AccountStatus::Disabled
                || $email === null) {
                return InstitutionalEmailVerificationResult::denied('institutional_verification_unavailable');
            }

            $latest = InstitutionalEmailVerificationChallenge::query()
                ->where('user_id', $lockedStudent->id)
                ->where('purpose', self::PURPOSE)
                ->latest('issued_at')
                ->lockForUpdate()
                ->first();
            $cooldownSeconds = (int) config('identity.institutional_email_verification_resend_cooldown_seconds', 60);

            if ($latest !== null && $now->lt($latest->issued_at->addSeconds($cooldownSeconds))) {
                return InstitutionalEmailVerificationResult::denied('verification_resend_cooldown');
            }

            $rollingHourCount = InstitutionalEmailVerificationChallenge::query()
                ->where('user_id', $lockedStudent->id)
                ->where('purpose', self::PURPOSE)
                ->where('issued_at', '>', $now->subHour())
                ->count();

            if ($rollingHourCount >= $hourlyLimit) {
                return InstitutionalEmailVerificationResult::denied('verification_hourly_limit_reached');
            }

            InstitutionalEmailVerificationChallenge::query()
                ->where('user_id', $lockedStudent->id)
                ->where('purpose', self::PURPOSE)
                ->whereNull('consumed_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $now]);

            $token = $this->newToken();
            $expiresAt = $now->addMinutes((int) config('identity.institutional_email_verification_lifetime_minutes', 30));
            $challenge = InstitutionalEmailVerificationChallenge::query()->create([
                'user_id' => $lockedStudent->id,
                'purpose' => self::PURPOSE,
                'token_hash' => $this->hashToken($token),
                'email_fingerprint' => $this->fingerprint($email),
                'issued_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            return [$challenge->id, new InstitutionalEmailVerificationMessage(
                $lockedStudent->id,
                $email,
                $token,
                $expiresAt,
            )];
        });

        if ($prepared instanceof InstitutionalEmailVerificationResult) {
            return $prepared;
        }

        [$challengeId, $message] = $prepared;

        try {
            $delivered = $this->delivery->deliver($message);
        } catch (Throwable) {
            $delivered = false;
        }

        if (! $delivered) {
            $this->revokeUndelivered($challengeId, $now);

            return InstitutionalEmailVerificationResult::denied('verification_delivery_unavailable');
        }

        return DB::transaction(function () use ($challengeId, $message, $now): InstitutionalEmailVerificationResult {
            $lockedStudent = User::query()->lockForUpdate()->find($message->studentId);
            $challenge = InstitutionalEmailVerificationChallenge::query()->lockForUpdate()->find($challengeId);
            $currentEmail = $lockedStudent === null || ! is_string($lockedStudent->email)
                ? null
                : $this->emailPolicy->normalizeAllowedAddress($lockedStudent->email);

            if ($challenge === null
                || $lockedStudent === null
                || $challenge->revoked_at !== null
                || $currentEmail === null
                || ! hash_equals($challenge->email_fingerprint, $this->fingerprint($currentEmail))) {
                if ($challenge !== null && $challenge->revoked_at === null) {
                    $challenge->forceFill(['revoked_at' => $now])->save();
                }

                return InstitutionalEmailVerificationResult::denied('verification_delivery_unavailable');
            }

            $challenge->forceFill(['delivered_at' => $now])->save();
            $this->events->record($lockedStudent, 'institutional_verification_issued');

            return InstitutionalEmailVerificationResult::issued();
        });
    }

    public function consume(User $student, #[\SensitiveParameter] string $token): InstitutionalEmailVerificationResult
    {
        if (! config('identity.institutional_email_verification_enabled')) {
            return InstitutionalEmailVerificationResult::denied('institutional_verification_disabled');
        }

        $now = $this->clock->now();

        return DB::transaction(function () use ($student, $token, $now): InstitutionalEmailVerificationResult {
            $lockedStudent = User::query()->lockForUpdate()->find($student->getKey());
            if ($lockedStudent === null
                || $lockedStudent->role !== AccountRole::Student
                || $lockedStudent->account_status === AccountStatus::Disabled) {
                return InstitutionalEmailVerificationResult::denied('institutional_verification_invalid');
            }

            $challenge = InstitutionalEmailVerificationChallenge::query()
                ->where('user_id', $lockedStudent->id)
                ->where('purpose', self::PURPOSE)
                ->where('token_hash', $this->hashToken($token))
                ->lockForUpdate()
                ->first();
            $email = is_string($lockedStudent->email)
                ? $this->emailPolicy->normalizeAllowedAddress($lockedStudent->email)
                : null;

            if ($challenge === null
                || $challenge->delivered_at === null
                || $challenge->consumed_at !== null
                || $challenge->revoked_at !== null
                || ! $now->lt($challenge->expires_at)
                || $email === null
                || ! hash_equals($challenge->email_fingerprint, $this->fingerprint($email))) {
                return InstitutionalEmailVerificationResult::denied('institutional_verification_invalid');
            }

            $challenge->forceFill(['consumed_at' => $now])->save();
            $lockedStudent->forceFill(['email_verified_at' => $now])->save();
            $this->events->record($lockedStudent, 'institutional_mailbox_verified');

            return InstitutionalEmailVerificationResult::verified();
        });
    }

    private function revokeUndelivered(string $challengeId, CarbonImmutable $now): void
    {
        InstitutionalEmailVerificationChallenge::query()
            ->whereKey($challengeId)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $now]);
    }

    private function newToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function hashToken(#[\SensitiveParameter] string $token): string
    {
        return hash_hmac('sha256', $token, $this->key());
    }

    private function fingerprint(string $email): string
    {
        return hash_hmac('sha256', 'institutional-email:'.$email, $this->key());
    }

    private function key(): string
    {
        $key = (string) config('app.key');
        if ($key === '') {
            throw new \LogicException('An application key is required for institutional verification hashing.');
        }

        return $key;
    }
}
