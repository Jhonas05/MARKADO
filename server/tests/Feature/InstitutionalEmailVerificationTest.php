<?php

namespace Tests\Feature;

use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Contracts\InstitutionalEmailVerificationService;
use App\Identity\Contracts\InstitutionalVerificationAbuseGate;
use App\Identity\Contracts\InstitutionalVerificationClock;
use App\Identity\Data\InstitutionalEmailVerificationMessage;
use App\Identity\Data\InstitutionalEmailVerificationResult;
use App\Identity\Data\StudentIdentityDecision;
use App\Identity\Support\DefaultDenyInstitutionalVerificationAbuseGate;
use App\Identity\Support\LaravelMailInstitutionalEmailVerificationDelivery;
use App\Identity\Support\UnavailableInstitutionalEmailVerificationDelivery;
use App\Mail\InstitutionalEmailVerificationMail;
use App\Models\InstitutionalEmailVerificationChallenge;
use App\Models\StudentIdentityEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\Support\MutableInstitutionalVerificationClock;
use Tests\TestCase;

class InstitutionalEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private MutableInstitutionalVerificationClock $clock;

    private CapturingVerificationDelivery $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('identity.institutional_email_domains', ['students.example.edu']);
        config()->set('identity.institutional_email_verification_enabled', true);
        config()->set('identity.institutional_email_verification_delivery_enabled', true);

        $this->clock = new MutableInstitutionalVerificationClock(
            CarbonImmutable::parse('2026-10-25 01:00:00', 'UTC'),
        );
        $this->delivery = new CapturingVerificationDelivery;
        $this->app->instance(InstitutionalVerificationClock::class, $this->clock);
        $this->app->instance(InstitutionalEmailVerificationDelivery::class, $this->delivery);
        $this->app->instance(InstitutionalVerificationAbuseGate::class, new AllowVerificationAbuseGate);
    }

    public function test_issue_uses_random_hash_only_single_live_challenges(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);

        $first = $service->issue($student);
        $firstToken = $this->delivery->latest()->token;
        $firstChallenge = InstitutionalEmailVerificationChallenge::query()->sole();

        $this->assertTrue($first->successful);
        $this->assertSame(43, strlen($firstToken));
        $this->assertNotSame($firstToken, $firstChallenge->getRawOriginal('token_hash'));
        $this->assertStringNotContainsString($firstToken, json_encode($firstChallenge->getAttributes(), JSON_THROW_ON_ERROR));
        $this->assertTrue($firstChallenge->expires_at->equalTo($firstChallenge->issued_at->addMinutes(30)));
        $this->assertNotNull($firstChallenge->delivered_at);

        $this->clock->current = $this->clock->current->addSeconds(60);
        $second = $service->issue($student->fresh());
        $secondToken = $this->delivery->latest()->token;

        $this->assertTrue($second->successful);
        $this->assertNotSame($firstToken, $secondToken);
        $this->assertNotNull($firstChallenge->fresh()->revoked_at);
        $this->assertSame(1, InstitutionalEmailVerificationChallenge::query()
            ->whereNull('consumed_at')->whereNull('revoked_at')->count());
    }

    public function test_valid_token_is_consumed_once_without_activating_student(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $service->issue($student);
        $token = $this->delivery->latest()->token;

        $first = $service->consume($student->fresh(), $token);
        $replay = $service->consume($student->fresh(), $token);

        $this->assertTrue($first->successful);
        $this->assertFalse($replay->successful);
        $this->assertSame('institutional_verification_invalid', $replay->reason);
        $this->assertNotNull($student->fresh()->email_verified_at);
        $this->assertSame(AccountStatus::Pending, $student->fresh()->account_status);
        $this->assertSame(AccountRole::Student, $student->fresh()->role);
        $this->assertNotNull(InstitutionalEmailVerificationChallenge::query()->sole()->consumed_at);
        $this->assertSame(1, StudentIdentityEvent::query()
            ->where('outcome', 'institutional_mailbox_verified')->count());
    }

    public function test_invalid_token_and_wrong_account_do_not_modify_verification(): void
    {
        $student = $this->student();
        $other = $this->student('other@students.example.edu');
        $service = app(InstitutionalEmailVerificationService::class);
        $service->issue($student);
        $token = $this->delivery->latest()->token;

        $this->assertFalse($service->consume($student, 'not-the-token')->successful);
        $this->assertFalse($service->consume($other, $token)->successful);
        $this->assertNull($student->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
        $this->assertNull(InstitutionalEmailVerificationChallenge::query()->sole()->consumed_at);
    }

    public function test_disabled_account_and_default_abuse_gate_fail_closed(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $this->assertTrue($service->issue($student)->successful);
        $token = $this->delivery->latest()->token;

        $student->forceFill(['account_status' => AccountStatus::Disabled])->save();
        $this->assertSame(
            'institutional_verification_invalid',
            $service->consume($student->fresh(), $token)->reason,
        );

        $other = $this->student();
        $this->app->instance(
            InstitutionalVerificationAbuseGate::class,
            new DefaultDenyInstitutionalVerificationAbuseGate,
        );
        $denied = app(InstitutionalEmailVerificationService::class)->issue($other);

        $this->assertFalse($denied->successful);
        $this->assertSame('verification_abuse_policy_unavailable', $denied->reason);
        $this->assertSame(1, InstitutionalEmailVerificationChallenge::query()->count());
    }

    public function test_exact_expiration_boundary_is_rejected(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $service->issue($student);
        $message = $this->delivery->latest();

        $this->clock->current = $message->expiresAt->subSecond();
        $this->assertTrue($service->consume($student, $message->token)->successful);

        $atBoundary = $this->student('boundary@students.example.edu');
        $this->clock->current = CarbonImmutable::parse('2026-10-25 02:00:00', 'UTC');
        $service->issue($atBoundary);
        $boundaryMessage = $this->delivery->latest();
        $this->clock->current = $boundaryMessage->expiresAt;

        $this->assertFalse($service->consume($atBoundary, $boundaryMessage->token)->successful);
        $this->clock->current = $boundaryMessage->expiresAt->addMicrosecond();
        $this->assertFalse($service->consume($atBoundary, $boundaryMessage->token)->successful);
        $this->assertNull($atBoundary->fresh()->email_verified_at);
    }

    public function test_resend_cooldown_rejects_before_and_allows_at_sixty_seconds(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $service->issue($student);
        $firstIssuedAt = $this->clock->current;

        $this->clock->current = $firstIssuedAt->addSeconds(59)->addMilliseconds(999);
        $early = $service->issue($student);
        $this->assertFalse($early->successful);
        $this->assertSame('verification_resend_cooldown', $early->reason);
        $this->assertCount(1, $this->delivery->messages);

        $this->clock->current = $firstIssuedAt->addSeconds(60);
        $this->assertTrue($service->issue($student)->successful);
        $this->assertCount(2, $this->delivery->messages);
    }

    public function test_explicit_synthetic_hourly_limit_uses_a_rolling_window(): void
    {
        config()->set('identity.institutional_email_verification_hourly_limit', 2);
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $start = $this->clock->current;

        $this->assertTrue($service->issue($student)->successful);
        $this->clock->current = $start->addSeconds(60);
        $this->assertTrue($service->issue($student)->successful);
        $this->clock->current = $start->addSeconds(120);
        $limited = $service->issue($student);

        $this->assertFalse($limited->successful);
        $this->assertSame('verification_hourly_limit_reached', $limited->reason);

        $this->clock->current = $start->addHour();
        $this->assertTrue($service->issue($student)->successful);
    }

    public function test_default_limit_counts_initial_issue_and_resends_then_rejects_the_sixth(): void
    {
        $this->assertSame(5, config('identity.institutional_email_verification_hourly_limit'));
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $start = $this->clock->current;

        foreach (range(0, 4) as $index) {
            $this->clock->current = $start->addSeconds($index * 60);
            $this->assertTrue($service->issue($student->fresh())->successful);
        }

        $this->clock->current = $start->addSeconds(300);
        $sixth = $service->issue($student->fresh());

        $this->assertFalse($sixth->successful);
        $this->assertSame('verification_hourly_limit_reached', $sixth->reason);
        $this->assertCount(5, $this->delivery->messages);
        $this->assertDatabaseCount('institutional_email_verification_challenges', 5);
    }

    public function test_exact_rolling_hour_boundary_releases_the_oldest_issue(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $start = $this->clock->current;

        foreach (range(0, 4) as $index) {
            $this->clock->current = $start->addSeconds($index * 60);
            $this->assertTrue($service->issue($student->fresh())->successful);
        }

        $this->clock->current = $start->addHour();

        $this->assertTrue($service->issue($student->fresh())->successful);
        $this->assertDatabaseCount('institutional_email_verification_challenges', 6);
    }

    public function test_lower_limit_is_allowed_but_invalid_and_above_ceiling_values_fail_closed(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        config()->set('identity.institutional_email_verification_hourly_limit', 1);

        $this->assertTrue($service->issue($student)->successful);
        $this->clock->current = $this->clock->current->addSeconds(60);
        $this->assertSame('verification_hourly_limit_reached', $service->issue($student)->reason);

        foreach ([null, 0, 6, '5', 2.5, true] as $invalidLimit) {
            config()->set('identity.institutional_email_verification_hourly_limit', $invalidLimit);
            $other = $this->student();
            $result = $service->issue($other);

            $this->assertFalse($result->successful);
            $this->assertSame('verification_hourly_policy_unavailable', $result->reason);
        }
    }

    public function test_invalid_hourly_policy_and_default_delivery_fail_closed(): void
    {
        $student = $this->student();
        config()->set('identity.institutional_email_verification_hourly_limit', null);
        $this->assertSame(
            'verification_hourly_policy_unavailable',
            app(InstitutionalEmailVerificationService::class)->issue($student)->reason,
        );
        $this->assertDatabaseCount('institutional_email_verification_challenges', 0);

        config()->set('identity.institutional_email_verification_hourly_limit', 5);
        $this->app->forgetInstance(InstitutionalEmailVerificationDelivery::class);
        $this->app->bind(
            InstitutionalEmailVerificationDelivery::class,
            UnavailableInstitutionalEmailVerificationDelivery::class,
        );

        $this->assertSame(
            'verification_delivery_unavailable',
            app(InstitutionalEmailVerificationService::class)->issue($student)->reason,
        );
        $this->assertNotNull(InstitutionalEmailVerificationChallenge::query()->sole()->revoked_at);
    }

    public function test_failed_delivery_counts_toward_the_rolling_hour_ceiling(): void
    {
        $student = $this->student();
        $this->app->instance(InstitutionalEmailVerificationDelivery::class, new FailingVerificationDelivery);
        $service = app(InstitutionalEmailVerificationService::class);
        $start = $this->clock->current;

        foreach (range(0, 4) as $index) {
            $this->clock->current = $start->addSeconds($index * 60);
            $this->assertSame('verification_delivery_unavailable', $service->issue($student->fresh())->reason);
        }

        $this->clock->current = $start->addSeconds(300);
        $this->assertSame('verification_hourly_limit_reached', $service->issue($student->fresh())->reason);
        $this->assertSame(5, InstitutionalEmailVerificationChallenge::query()
            ->whereNotNull('revoked_at')->count());
    }

    public function test_delivery_finishing_at_expiry_is_revoked_and_never_becomes_consumable(): void
    {
        $student = $this->student();
        $delivery = new DelayedVerificationDelivery($this->clock, 30);
        $this->app->instance(InstitutionalEmailVerificationDelivery::class, $delivery);
        $service = app(InstitutionalEmailVerificationService::class);

        $result = $service->issue($student);
        $challenge = InstitutionalEmailVerificationChallenge::query()->sole();

        $this->assertFalse($result->successful);
        $this->assertSame('verification_delivery_unavailable', $result->reason);
        $this->assertNull($challenge->delivered_at);
        $this->assertNotNull($challenge->revoked_at);
        $this->assertFalse($service->consume($student->fresh(), $delivery->message->token)->successful);
    }

    public function test_email_change_during_delivery_revokes_the_issued_challenge(): void
    {
        $student = $this->student();
        $delivery = new EmailChangingVerificationDelivery($student, 'changed@students.example.edu');
        $this->app->instance(InstitutionalEmailVerificationDelivery::class, $delivery);

        $result = app(InstitutionalEmailVerificationService::class)->issue($student);

        $this->assertFalse($result->successful);
        $this->assertSame('verification_delivery_unavailable', $result->reason);
        $this->assertNotNull(InstitutionalEmailVerificationChallenge::query()->sole()->revoked_at);
        $this->assertNull($student->fresh()->email_verified_at);
    }

    public function test_same_bound_mailbox_shares_cooldown_and_hourly_budget_across_accounts(): void
    {
        $student = $this->student('shared@students.example.edu');
        $other = $this->student('shared@students.example.edu');
        $service = app(InstitutionalEmailVerificationService::class);

        $this->assertTrue($service->issue($student)->successful);
        $this->assertSame('verification_resend_cooldown', $service->issue($other)->reason);

        $start = $this->clock->current;
        foreach (range(1, 4) as $index) {
            $this->clock->current = $start->addSeconds($index * 60);
            $account = $index % 2 === 0 ? $student : $other;
            $this->assertTrue($service->issue($account->fresh())->successful);
        }

        $this->clock->current = $start->addSeconds(300);
        $this->assertSame('verification_hourly_limit_reached', $service->issue($other->fresh())->reason);
        $this->assertDatabaseCount('institutional_email_verification_challenges', 5);
    }

    public function test_email_change_clears_proof_revokes_challenge_and_blocks_old_token(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $service->issue($student);
        $token = $this->delivery->latest()->token;

        $student->forceFill([
            'email' => 'changed@students.example.edu',
            'email_verified_at' => $this->clock->current,
        ])->save();

        $this->assertNull($student->fresh()->email_verified_at);
        $this->assertNotNull(InstitutionalEmailVerificationChallenge::query()->sole()->revoked_at);
        $this->assertFalse($service->consume($student->fresh(), $token)->successful);
    }

    public function test_transaction_rollback_does_not_leave_consumption_or_mailbox_proof(): void
    {
        $student = $this->student();
        $service = app(InstitutionalEmailVerificationService::class);
        $service->issue($student);
        $token = $this->delivery->latest()->token;

        DB::beginTransaction();
        $this->assertTrue($service->consume($student, $token)->successful);
        DB::rollBack();

        $this->assertNull($student->fresh()->email_verified_at);
        $this->assertNull(InstitutionalEmailVerificationChallenge::query()->sole()->consumed_at);
        $this->assertDatabaseMissing('student_identity_events', [
            'user_id' => $student->id,
            'outcome' => 'institutional_mailbox_verified',
        ]);
    }

    public function test_generic_projection_is_identical_for_every_internal_outcome(): void
    {
        $results = [
            InstitutionalEmailVerificationResult::issued(),
            InstitutionalEmailVerificationResult::verified(),
            InstitutionalEmailVerificationResult::denied('account_missing'),
            InstitutionalEmailVerificationResult::denied('verification_resend_cooldown'),
        ];

        $this->assertCount(1, collect($results)->map(
            fn (InstitutionalEmailVerificationResult $result) => $result->publicAcknowledgement(),
        )->unique());
    }

    public function test_laravel_mail_adapter_targets_only_the_bound_recipient(): void
    {
        Mail::fake();
        $message = new InstitutionalEmailVerificationMessage(
            123,
            'student@students.example.edu',
            'synthetic-secret',
            $this->clock->current->addMinutes(30),
        );

        $this->assertTrue((new LaravelMailInstitutionalEmailVerificationDelivery)->deliver($message));
        Mail::assertSent(InstitutionalEmailVerificationMail::class, fn ($mail) => $mail->hasTo('student@students.example.edu')
            && $mail->verificationToken === 'synthetic-secret');
        Mail::assertSentCount(1);
    }

    public function test_verification_and_registration_mutation_routes_and_feature_flags_remain_disabled(): void
    {
        config()->set('identity.institutional_email_verification_enabled', false);
        config()->set('identity.institutional_email_verification_delivery_enabled', false);

        foreach (['register', 'verify-email', 'email/verification-notification', 'activate'] as $uri) {
            $this->post($uri)->assertStatus(405);
        }

        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route) => preg_match('/register|verify|verification|activate|roster/', $route->uri()) === 1
            && array_intersect(['POST', 'PUT', 'PATCH', 'DELETE'], $route->methods()) !== []));
        $this->assertFalse(config('identity.student_self_registration_enabled'));
        $this->assertFalse(config('identity.automatic_student_activation_enabled'));
        $this->assertFalse(config('identity.institutional_email_verification_enabled'));
        $this->assertFalse(config('identity.institutional_email_verification_delivery_enabled'));
        $this->assertFalse(config('assessment.routes_enabled'));
        $this->assertFalse(config('assessment.expiration_enabled'));
    }

    private function student(?string $email = null): User
    {
        return User::factory()->create([
            'email' => $email ?? 'student-'.fake()->unique()->numerify('########').'@students.example.edu',
            'role' => AccountRole::Student,
            'account_status' => AccountStatus::Pending,
            'email_verified_at' => null,
        ]);
    }
}

final class CapturingVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    /** @var list<InstitutionalEmailVerificationMessage> */
    public array $messages = [];

    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        $this->messages[] = $message;

        return true;
    }

    public function latest(): InstitutionalEmailVerificationMessage
    {
        return $this->messages[array_key_last($this->messages)];
    }
}

final class FailingVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        return false;
    }
}

final class DelayedVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    public InstitutionalEmailVerificationMessage $message;

    public function __construct(
        private readonly MutableInstitutionalVerificationClock $clock,
        private readonly int $minutes,
    ) {}

    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        $this->message = $message;
        $this->clock->current = $this->clock->current->addMinutes($this->minutes);

        return true;
    }
}

final class EmailChangingVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    public function __construct(
        private readonly User $student,
        private readonly string $replacementEmail,
    ) {}

    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        $this->student->forceFill(['email' => $this->replacementEmail])->save();

        return true;
    }
}

final class AllowVerificationAbuseGate implements InstitutionalVerificationAbuseGate
{
    public function evaluate(User $student, ?string $networkContext): StudentIdentityDecision
    {
        return StudentIdentityDecision::matched();
    }
}
