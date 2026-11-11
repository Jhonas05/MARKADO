<?php

namespace Tests\Feature;

use App\Identity\Data\InstitutionalEmailVerificationMessage;
use App\Identity\Support\ResendHttpInstitutionalEmailVerificationDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ResendHttpInstitutionalEmailVerificationDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:synthetic-test-key');
        config()->set('services.resend.key', 're_test_synthetic');
        config()->set('mail.from.address', 'verification@markado.example');
        config()->set('mail.from.name', 'MARKADO');
    }

    public function test_https_adapter_sends_the_bound_recipient_with_idempotency_and_bearer_auth(): void
    {
        Http::fake([
            'https://api.resend.com/emails' => Http::response(['id' => 'email_synthetic_123'], 200),
        ]);
        Log::spy();

        $message = $this->message();
        $this->assertTrue((new ResendHttpInstitutionalEmailVerificationDelivery)->deliver($message));

        Http::assertSent(function (Request $request) use ($message): bool {
            $idempotencyKey = $request->header('Idempotency-Key')[0] ?? '';

            return $request->url() === 'https://api.resend.com/emails'
                && $request->hasHeader('Authorization', 'Bearer re_test_synthetic')
                && str_starts_with($idempotencyKey, 'markado-verification-')
                && ! str_contains($idempotencyKey, $message->token)
                && $request['from'] === 'MARKADO <verification@markado.example>'
                && $request['to'] === [$message->recipient]
                && $request['subject'] === 'Verify your MARKADO institutional email'
                && str_contains($request['text'], $message->token);
        });
        Http::assertSentCount(1);
        Log::shouldHaveReceived('info')->once()->with(
            'Institutional verification delivery accepted.',
            ['provider' => 'resend'],
        );
    }

    public function test_http_failure_returns_false_and_logs_no_recipient_token_or_provider_response(): void
    {
        Http::fake([
            'https://api.resend.com/emails' => Http::response([
                'message' => 'provider response containing student@students.example.edu and synthetic-secret',
            ], 422),
        ]);
        Log::spy();

        $this->assertFalse((new ResendHttpInstitutionalEmailVerificationDelivery)->deliver($this->message()));

        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $summary, array $context): bool {
            $serialized = $summary.' '.json_encode($context, JSON_THROW_ON_ERROR);

            return $summary === 'Institutional verification delivery failed.'
                && $context === [
                    'provider' => 'resend',
                    'failure' => 'http_response',
                    'status' => 422,
                ]
                && ! str_contains($serialized, 'student@students.example.edu')
                && ! str_contains($serialized, 'synthetic-secret');
        });
    }

    public function test_missing_provider_key_fails_closed_without_an_http_request(): void
    {
        config()->set('services.resend.key', '');
        Http::fake();
        Log::spy();

        $this->assertFalse((new ResendHttpInstitutionalEmailVerificationDelivery)->deliver($this->message()));

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')->once()->with(
            'Institutional verification delivery failed.',
            ['provider' => 'resend', 'failure' => 'configuration'],
        );
    }

    private function message(): InstitutionalEmailVerificationMessage
    {
        return new InstitutionalEmailVerificationMessage(
            123,
            'student@students.example.edu',
            'synthetic-secret',
            CarbonImmutable::parse('2026-11-12T10:30:00+00:00'),
        );
    }
}
