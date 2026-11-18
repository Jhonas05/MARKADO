<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Help', [
            'topics' => [
                [
                    'title' => 'Student registration and sign in',
                    'steps' => [
                        'Enter the exact official student number and institutional email on the registration page.',
                        'Open the verification message within 30 minutes. A new message can be requested after 60 seconds, up to five times per rolling hour.',
                        'Registration activates only when the mailbox and the same active, verified school roster record both match.',
                    ],
                ],
                [
                    'title' => 'Taking a quiz',
                    'steps' => [
                        'Join with the Professor-provided code and review the server-authoritative schedule before starting.',
                        'Wait for the Saved state after each answer. If the connection drops, MARKADO keeps only server-acknowledged answers.',
                        'The server submits at the effective deadline. Refreshing cannot extend time or create another attempt.',
                    ],
                ],
                [
                    'title' => 'Professor workflow',
                    'steps' => [
                        'Create a draft, add answer-keyed questions, set the schedule, and publish an immutable revision.',
                        'Assign only verified students. Review scored submissions before releasing each academic result.',
                        'Use the results page to sort the report and export PDF or DOCX. Exports never change grades or release state.',
                    ],
                ],
                [
                    'title' => 'Results, privacy, and support',
                    'steps' => [
                        'Students see only a released earned score and maximum score; answer keys remain private.',
                        'Never send passwords or verification codes to staff. Use the institution-approved support channel for roster or account corrections.',
                        'If a timer, save, or submission looks wrong, preserve the receipt ID and contact support without retrying from another account.',
                    ],
                ],
            ],
        ]);
    }
}
