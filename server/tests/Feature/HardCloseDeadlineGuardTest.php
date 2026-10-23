<?php

namespace Tests\Feature;

use App\Assessment\Support\HardCloseDeadlineGuard;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class HardCloseDeadlineGuardTest extends TestCase
{
    public function test_start_is_allowed_strictly_before_closing(): void
    {
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T09:59:59.999999+08:00'));
        $guard = new HardCloseDeadlineGuard($clock);

        $this->assertTrue($guard->mayStart(new DateTimeImmutable('2026-10-23T10:00:00+08:00')));
    }

    #[DataProvider('closedStartTimes')]
    public function test_start_is_denied_at_or_after_closing(string $serverTime): void
    {
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable($serverTime));
        $guard = new HardCloseDeadlineGuard($clock);

        $this->assertFalse($guard->mayStart(new DateTimeImmutable('2026-10-23T10:00:00+08:00')));
    }

    public static function closedStartTimes(): array
    {
        return [
            'exact close' => ['2026-10-23T10:00:00+08:00'],
            'after close' => ['2026-10-23T10:00:00.000001+08:00'],
        ];
    }

    public function test_effective_attempt_deadline_is_capped_by_quiz_close(): void
    {
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T09:50:00+08:00'));
        $guard = new HardCloseDeadlineGuard($clock);
        $close = new DateTimeImmutable('2026-10-23T10:00:00+08:00');

        $plan = $guard->planAttempt($clock->now(), 20 * 60, $close);

        $this->assertSame('2026-10-23T10:10:00+08:00', $plan->sessionDeadlineAt?->format('c'));
        $this->assertSame($close->format('c'), $plan->effectiveDeadlineAt->format('c'));
    }

    public function test_shorter_session_deadline_remains_effective(): void
    {
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T09:00:00+08:00'));
        $guard = new HardCloseDeadlineGuard($clock);

        $plan = $guard->planAttempt(
            $clock->now(),
            20 * 60,
            new DateTimeImmutable('2026-10-23T10:00:00+08:00'),
        );

        $this->assertSame('2026-10-23T09:20:00+08:00', $plan->effectiveDeadlineAt->format('c'));
    }

    public function test_global_close_caps_an_active_question_deadline(): void
    {
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T09:50:00+08:00'));
        $guard = new HardCloseDeadlineGuard($clock);
        $close = new DateTimeImmutable('2026-10-23T10:00:00+08:00');

        $capped = $guard->capQuestionDeadline(
            new DateTimeImmutable('2026-10-23T10:05:00+08:00'),
            $close,
        );

        $this->assertSame($close->format('c'), $capped->format('c'));
    }

    public function test_browser_clock_cannot_change_server_boundary_decisions(): void
    {
        $clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-23T10:00:00+08:00'));
        $guard = new HardCloseDeadlineGuard($clock);
        $browserReportedTime = new DateTimeImmutable('2026-10-23T09:40:00+08:00');
        $close = new DateTimeImmutable('2026-10-23T10:00:00+08:00');

        $this->assertLessThan($close, $browserReportedTime);
        $this->assertFalse($guard->mayStart($close));
        $this->assertTrue($guard->hasReached($close));
    }

    public function test_attempt_plan_rejects_a_start_at_closing(): void
    {
        $close = new DateTimeImmutable('2026-10-23T10:00:00+08:00');
        $guard = new HardCloseDeadlineGuard(new MutableAuthoritativeClock($close));

        $this->expectException(DomainException::class);
        $guard->planAttempt($close, 1200, $close);
    }
}
