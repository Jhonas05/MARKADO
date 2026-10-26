<?php

namespace Tests\Feature;

use App\Assessment\Contracts\AttemptFinalizer;
use App\Assessment\Contracts\AttemptSessionManager;
use App\Assessment\Contracts\AuthoritativeClock;
use App\Enums\AccountRole;
use App\Enums\AccountStatus;
use App\Gamification\Support\CharacterCatalogSynchronizer;
use App\Gamification\Support\CharacterEconomyService;
use App\Models\AttemptAnswer;
use App\Models\AttemptCoinReward;
use App\Models\Character;
use App\Models\CoinLedgerEntry;
use App\Models\QuizAttempt;
use App\Models\StudentCharacter;
use App\Models\StudentWallet;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\BuildsAssessmentFixtures;
use Tests\Support\MutableAuthoritativeClock;
use Tests\TestCase;

class GamificationEconomyTest extends TestCase
{
    use BuildsAssessmentFixtures;
    use RefreshDatabase;

    private MutableAuthoritativeClock $clock;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('gamification.enabled', true);
        config()->set('gamification.completion_reward', 10);
        config()->set('assessment.routes_enabled', true);
        $this->clock = new MutableAuthoritativeClock(new DateTimeImmutable('2026-10-24T01:00:00+00:00'));
        $this->app->instance(AuthoritativeClock::class, $this->clock);
        app(CharacterCatalogSynchronizer::class)->sync();
    }

    public function test_catalog_and_starter_character_are_idempotently_provisioned(): void
    {
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $economy = app(CharacterEconomyService::class);

        $first = $economy->provisionStarterCharacter($student);
        $second = $economy->provisionStarterCharacter($student);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('byte', $student->wallet()->with('equippedCharacter')->firstOrFail()->equippedCharacter->slug);
        $this->assertSame(1, StudentCharacter::query()->where('student_id', $student->id)->count());
        $this->assertSame(3, Character::query()->count());
    }

    public function test_wrong_answer_completion_awards_ten_coins_once_without_revealing_withheld_grade(): void
    {
        [, $student, $quiz] = $this->assessmentFixture(questionCount: 1, randomize: false);
        $attempt = $this->attemptWithAcknowledgedAnswer($student, $quiz, correct: false);

        $first = app(AttemptFinalizer::class)->submitByStudent($attempt);
        $second = app(AttemptFinalizer::class)->submitByStudent($attempt->fresh());

        $this->assertFalse($first->alreadyFinalized);
        $this->assertTrue($second->alreadyFinalized);
        $this->assertSame(10, StudentWallet::query()->findOrFail($student->id)->balance);
        $this->assertSame(1, AttemptCoinReward::query()->where('attempt_id', $attempt->id)->count());
        $this->assertSame(1, CoinLedgerEntry::query()->where('attempt_id', $attempt->id)->count());
        $this->assertDatabaseHas('attempt_results', ['attempt_id' => $attempt->id, 'earned_points' => 0, 'status' => 'scored']);

        $this->actingAs($student)->get("/student/attempts/{$attempt->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('attempt.reward.coins', 10)
                ->where('attempt.result.state', 'withheld')
                ->missing('attempt.result.earnedPoints')
                ->missing('attempt.result.maximumPoints'));
    }

    public function test_entirely_unanswered_terminal_attempt_earns_no_reward(): void
    {
        [, $student, $quiz] = $this->assessmentFixture(questionCount: 1, randomize: false);
        $session = app(AttemptSessionManager::class)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->findOrFail($session->attemptId);

        $this->clock->advanceTo(new DateTimeImmutable('2026-10-24T01:11:00+00:00'));
        app(AttemptFinalizer::class)->finalizeAtEffectiveDeadline($attempt);

        $this->assertDatabaseMissing('attempt_coin_rewards', ['attempt_id' => $attempt->id]);
        $this->assertDatabaseMissing('student_wallets', ['student_id' => $student->id]);
    }

    public function test_purchase_uses_server_price_debits_atomically_and_denies_duplicate_or_insufficient_balance(): void
    {
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $economy = app(CharacterEconomyService::class);
        $economy->provisionStarterCharacter($student);
        StudentWallet::query()->whereKey($student->id)->update(['balance' => 10]);
        $mochi = Character::query()->where('slug', 'mochi')->firstOrFail();
        $pixel = Character::query()->where('slug', 'pixel')->firstOrFail();

        $this->actingAs($student)->post("/student/characters/{$mochi->id}/purchase", [
            'price' => 0,
            'balance' => 999999,
            'student_id' => User::factory()->create(['role' => AccountRole::Student])->id,
        ])->assertRedirect('/student/characters/collection')->assertSessionHas('success');

        $this->assertSame(0, $student->wallet()->firstOrFail()->balance);
        $this->assertDatabaseHas('student_characters', [
            'student_id' => $student->id,
            'character_id' => $mochi->id,
            'price_paid' => 10,
        ]);
        $this->assertDatabaseHas('coin_ledger_entries', [
            'student_id' => $student->id,
            'character_id' => $mochi->id,
            'amount' => -10,
            'balance_after' => 0,
        ]);

        $this->from('/student/characters/shop')->post("/student/characters/{$mochi->id}/purchase")
            ->assertRedirect('/student/characters/shop')->assertSessionHasErrors('purchase');
        $this->from('/student/characters/shop')->post("/student/characters/{$pixel->id}/purchase")
            ->assertRedirect('/student/characters/shop')->assertSessionHasErrors('purchase');

        $this->assertSame(0, $student->wallet()->firstOrFail()->balance);
        $this->assertSame(1, CoinLedgerEntry::query()->where('entry_type', 'character_purchase')->count());
    }

    public function test_collection_equip_requires_current_students_ownership_and_persists_across_logout(): void
    {
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $other = User::factory()->create(['role' => AccountRole::Student]);
        $economy = app(CharacterEconomyService::class);
        $economy->provisionStarterCharacter($student);
        $economy->provisionStarterCharacter($other);
        $mochi = Character::query()->where('slug', 'mochi')->firstOrFail();
        StudentWallet::query()->whereKey($student->id)->update(['balance' => 10]);
        $economy->purchase($student, $mochi);

        $this->actingAs($other)->from('/student/characters/collection')
            ->post("/student/characters/{$mochi->id}/equip")
            ->assertRedirect('/student/characters/collection')
            ->assertSessionHasErrors('equip');
        $this->assertNotSame($mochi->id, $other->wallet()->firstOrFail()->equipped_character_id);

        $this->actingAs($student)->post("/student/characters/{$mochi->id}/equip")->assertRedirect();
        $this->delete('/logout')->assertRedirect('/login');
        $this->assertGuest();

        $this->actingAs($student)->get('/student/characters/collection')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Gamification/Collection')
                ->where('economy.equippedCharacter.slug', 'mochi')
                ->where('gamification.equippedCharacter.slug', 'mochi'));
    }

    public function test_inactive_missing_and_unowned_catalog_entries_fail_closed(): void
    {
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $economy = app(CharacterEconomyService::class);
        $economy->provisionStarterCharacter($student);
        StudentWallet::query()->whereKey($student->id)->update(['balance' => 100]);
        $pixel = Character::query()->where('slug', 'pixel')->firstOrFail();
        $pixel->update(['is_active' => false]);

        $this->actingAs($student)->from('/student/characters/shop')
            ->post("/student/characters/{$pixel->id}/purchase")
            ->assertRedirect('/student/characters/shop')
            ->assertSessionHasErrors('purchase');
        $this->post('/student/characters/00000000-0000-4000-8000-000000000099/purchase')->assertNotFound();
        $this->assertSame(100, $student->wallet()->firstOrFail()->balance);
        $this->assertDatabaseMissing('student_characters', [
            'student_id' => $student->id,
            'character_id' => $pixel->id,
        ]);

        $this->expectException(QueryException::class);
        StudentWallet::query()->whereKey($student->id)->update(['equipped_character_id' => $pixel->id]);
    }

    public function test_routes_are_student_only_and_default_off(): void
    {
        $student = User::factory()->create(['role' => AccountRole::Student]);
        $instructor = User::factory()->create(['role' => AccountRole::Instructor]);

        $this->actingAs($instructor)->get('/student/characters/shop')->assertForbidden();

        config()->set('gamification.enabled', false);
        $this->actingAs($student)->get('/student/characters/shop')->assertStatus(503);
    }

    public function test_logout_is_delete_only_for_both_authenticated_roles(): void
    {
        foreach ([AccountRole::Student, AccountRole::Instructor] as $role) {
            $user = User::factory()->create(['role' => $role, 'account_status' => AccountStatus::Active]);
            $this->actingAs($user)->get('/logout')->assertNotFound();
            $this->delete('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    private function attemptWithAcknowledgedAnswer(User $student, $quiz, bool $correct): QuizAttempt
    {
        $session = app(AttemptSessionManager::class)->resumeOrCreate($student, $quiz);
        $attempt = QuizAttempt::query()->with('questionSnapshots')->findOrFail($session->attemptId);
        $snapshot = $attempt->questionSnapshots->firstOrFail();
        $choiceId = $correct
            ? $snapshot->correct_choice_id
            : collect($snapshot->choices)->first(fn (array $choice): bool => $choice['id'] !== $snapshot->correct_choice_id)['id'];
        $answer = new AttemptAnswer(['selected_choice_id' => $choiceId, 'version' => 1, 'saved_at' => now()]);
        $answer->attempt()->associate($attempt);
        $answer->questionSnapshot()->associate($snapshot);
        $answer->save();

        return $attempt;
    }
}
