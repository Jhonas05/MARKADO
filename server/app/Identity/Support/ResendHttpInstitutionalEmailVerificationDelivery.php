<?php

namespace App\Identity\Support;

use App\Identity\Contracts\InstitutionalEmailVerificationDelivery;
use App\Identity\Data\InstitutionalEmailVerificationMessage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ResendHttpInstitutionalEmailVerificationDelivery implements InstitutionalEmailVerificationDelivery
{
    private const ENDPOINT = 'https://api.resend.com/emails';

    public function deliver(InstitutionalEmailVerificationMessage $message): bool
    {
        $apiKey = config('services.resend.key');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $applicationKey = config('app.key');

        if (! is_string($apiKey) || trim($apiKey) === ''
            || ! is_string($applicationKey) || $applicationKey === ''
            || ! is_string($fromAddress) || filter_var($fromAddress, FILTER_VALIDATE_EMAIL) === false
            || ! is_string($fromName) || preg_match('/[\r\n]/', $fromName) === 1
            || preg_match('/[\r\n]/', $fromAddress) === 1
            || filter_var($message->recipient, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/[\r\n]/', $message->recipient) === 1) {
            $this->reportFailure('configuration');

            return false;
        }

        $text = view('mail.institutional-email-verification', [
            'verificationToken' => $message->token,
            'expiresAt' => $message->expiresAt,
        ])->render();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withToken(trim($apiKey))
                ->withHeaders([
                    'Idempotency-Key' => $this->idempotencyKey($message),
                ])
                ->connectTimeout(3)
                ->timeout(8)
                ->retry(
                    2,
                    200,
                    static fn (Throwable $exception): bool => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException
                            && ($exception->response?->status() === 429
                                || $exception->response?->serverError())),
                    throw: false,
                )
                ->post(self::ENDPOINT, [
                    'from' => trim($fromName) === ''
                        ? $fromAddress
                        : trim($fromName).' <'.$fromAddress.'>',
                    'to' => [$message->recipient],
                    'subject' => 'Verify your MARKADO institutional email',
                    'text' => trim($text),
                ]);
        } catch (Throwable $exception) {
            $this->reportFailure($exception instanceof ConnectionException ? 'connection' : 'request');

            return false;
        }

        if (! $response->successful() || ! is_string($response->json('id')) || $response->json('id') === '') {
            $this->reportFailure('http_response', $response->status());

            return false;
        }

        Log::info('Institutional verification delivery accepted.', [
            'provider' => 'resend',
        ]);

        return true;
    }

    private function idempotencyKey(InstitutionalEmailVerificationMessage $message): string
    {
        return 'markado-verification-'.hash_hmac(
            'sha256',
            $message->studentId.'|'.$message->recipient.'|'.$message->token.'|'.$message->expiresAt->getTimestamp(),
            (string) config('app.key'),
        );
    }

    private function reportFailure(string $failure, ?int $status = null): void
    {
        Log::warning('Institutional verification delivery failed.', array_filter([
            'provider' => 'resend',
            'failure' => $failure,
            'status' => $status,
        ], static fn (mixed $value): bool => $value !== null));
    }
}
