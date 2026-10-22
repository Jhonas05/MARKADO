<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Student/Dashboard', [
            'student' => [
                'firstName' => 'Mika',
                'name' => 'Mika Santos',
                'studentNumber' => '2026-1042',
                'initials' => 'MS',
            ],
            'metrics' => [
                ['label' => 'Ready to take', 'value' => '1', 'detail' => 'This week', 'tone' => 'accent'],
                ['label' => 'Completed', 'value' => '3', 'detail' => 'October', 'tone' => 'success'],
                ['label' => 'Average score', 'value' => '88%', 'detail' => 'Synthetic data', 'tone' => 'info'],
            ],
            'quiz' => [
                'course' => 'BIO 104',
                'courseName' => 'General Biology',
                'title' => 'Cell Structure and Function',
                'instructor' => 'Prof. Lena Cruz',
                'schedule' => 'Practice window open',
                'durationMinutes' => 20,
                'questionCount' => 5,
            ],
            'recentResults' => [
                ['subject' => 'BIO 103', 'detail' => 'Genetics review', 'date' => 'Oct 18', 'score' => '9/10', 'percent' => 90],
                ['subject' => 'CHEM 101', 'detail' => 'Atomic structure', 'date' => 'Oct 12', 'score' => '17/20', 'percent' => 85],
                ['subject' => 'MATH 108', 'detail' => 'Functions', 'date' => 'Oct 4', 'score' => '22/25', 'percent' => 88],
            ],
            'foundation' => [
                'framework' => app()->version(),
                'mode' => 'Synthetic Inertia demonstration',
                'persistence' => 'No quiz data is persisted yet',
            ],
        ]);
    }
}
