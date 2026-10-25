<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AccountAccessController extends Controller
{
    public function register(): Response
    {
        return Inertia::render('Auth/Register', [
            'registration' => [
                'available' => false,
                'selfRegistrationApproved' => true,
                'automaticStudentActivationApproved' => true,
                'message' => 'Student self-registration and bounded automatic Student activation are approved directions. The public submission route stays disabled until mailbox delivery, anti-abuse, duplicate recovery, and school roster operations are approved and configured.',
                'requirements' => [
                    'Use your official student number and school-issued institutional email.',
                    'The number and email must match the same active, verified school roster record.',
                    'You must independently prove control of the institutional mailbox.',
                    'Activation remains separate from quiz assignment and assessment eligibility.',
                ],
            ],
        ]);
    }

    public function forgotPassword(): Response
    {
        return $this->policyGate(
            'Password recovery',
            'Recovery is not available yet',
            'The institution-managed email verification and account-recovery policy remains pending under D14.',
            'password-recovery',
        );
    }

    public function resetPassword(): Response
    {
        return $this->policyGate(
            'Password reset',
            'A verified reset link is required',
            'MARKADO has not enabled password reset issuance while the institutional recovery policy remains unresolved.',
            'password-reset',
        );
    }

    public function pending(): Response
    {
        return $this->policyGate(
            'Account pending',
            'Your account is awaiting verification',
            'You cannot enter an assessment workspace until an authorized institution workflow verifies and assigns the account.',
            'pending',
        );
    }

    public function disabled(): Response
    {
        return $this->policyGate(
            'Account disabled',
            'This account cannot sign in',
            'Contact your institution through its approved support channel. MARKADO does not expose internal account details here.',
            'disabled',
        );
    }

    public function sessionExpired(): Response
    {
        return $this->policyGate(
            'Session expired',
            'Please sign in again',
            'Your previous session is no longer valid. No assessment deadline is extended by signing in again.',
            'session-expired',
        );
    }

    private function policyGate(string $title, string $heading, string $message, string $state): Response
    {
        return Inertia::render('Auth/PolicyGate', compact('heading', 'message', 'state', 'title'));
    }
}
