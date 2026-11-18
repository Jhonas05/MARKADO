<?php

namespace App\Http\Controllers;

use App\Assessment\Contracts\ResultReleaseManager;
use App\Assessment\Support\AssessmentAuditRecorder;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Reporting\DocxReportWriter;
use App\Reporting\PdfReportWriter;
use App\Reporting\QuizResultReportBuilder;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ProfessorQuizResultController extends Controller
{
    public function index(Request $request, Quiz $quiz, QuizResultReportBuilder $reports): Response
    {
        $this->authorize('view', $quiz);
        $report = $reports->build($quiz, $request->string('sort')->toString());

        return Inertia::render('Professor/Quizzes/Results', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'courseCode' => $quiz->course_code,
            ],
            'attempts' => $report['attempts'],
            'summary' => $report['summary'],
            'sort' => $report['sort'],
        ]);
    }

    public function export(
        Request $request,
        Quiz $quiz,
        string $format,
        QuizResultReportBuilder $reports,
        PdfReportWriter $pdf,
        DocxReportWriter $docx,
        AssessmentAuditRecorder $audit,
    ): HttpResponse {
        $this->authorize('view', $quiz);
        abort_unless(in_array($format, ['pdf', 'docx'], true), 404);

        $report = $reports->build($quiz, $request->string('sort')->toString());
        $quizProjection = ['title' => $quiz->title, 'courseCode' => $quiz->course_code];
        $content = $format === 'pdf'
            ? $pdf->write($quizProjection, $report)
            : $docx->write($quizProjection, $report);
        $contentType = $format === 'pdf'
            ? 'application/pdf'
            : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $filename = Str::slug($quiz->course_code.'-'.$quiz->title).'-results.'.$format;

        $audit->recordQuizEvent($quiz, 'results_exported', $request->user(), [
            'format' => $format,
            'sort' => $report['sort'],
            'record_count' => $report['summary']['attemptCount'],
        ]);

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function release(
        Request $request,
        Quiz $quiz,
        QuizAttempt $attempt,
        ResultReleaseManager $releaseManager,
    ): RedirectResponse {
        abort_unless($attempt->quiz_id === $quiz->id, 404);
        $this->authorize('release', $attempt);

        try {
            $releaseManager->release($attempt, $request->user());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['release' => $exception->getMessage()]);
        }

        return back()->with('success', 'The score and total are now visible to this student.');
    }
}
