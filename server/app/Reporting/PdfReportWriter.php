<?php

namespace App\Reporting;

final class PdfReportWriter
{
    public function write(array $quiz, array $report): string
    {
        $lines = [
            'MARKADO Quiz Results',
            $quiz['courseCode'].' - '.$quiz['title'],
            'Generated: '.now()->timezone('Asia/Manila')->format('Y-m-d H:i').' Asia/Manila',
            'Order: '.($report['sort'] === 'score' ? 'Score (high to low), then student name' : 'Student name (A to Z)'),
            '',
            'Student | Student number | Score | Release | Submitted',
        ];

        foreach ($report['attempts'] as $attempt) {
            $score = $attempt['scoringStatus'] === 'scored'
                ? $attempt['earnedPoints'].'/'.$attempt['maximumPoints']
                : $attempt['scoringStatus'];
            $submitted = $attempt['submittedAt']
                ? date('Y-m-d H:i', strtotime($attempt['submittedAt']))
                : 'Not submitted';
            $lines[] = implode(' | ', [
                $attempt['studentName'],
                $attempt['studentNumber'] ?: 'Unavailable',
                $score,
                $attempt['releasedAt'] ? 'Released' : 'Withheld',
                $submitted,
            ]);
        }

        if ($report['attempts']->isEmpty()) {
            $lines[] = 'No assessment attempts recorded.';
        }

        $wrapped = collect($lines)->flatMap(
            fn (string $line): array => explode("\n", wordwrap($line, 95, "\n", true)),
        )->all();

        return $this->document(array_chunk($wrapped, 43));
    }

    private function document(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $kids = [];

        foreach ($pages as $index => $lines) {
            $pageObject = 4 + ($index * 2);
            $contentObject = $pageObject + 1;
            $kids[] = $pageObject.' 0 R';
            $stream = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
            foreach ($lines as $line) {
                $stream .= '('.$this->escape($line).") Tj\nT*\n";
            }
            $stream .= "ET\n";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject] = '<< /Length '.strlen($stream).">>\nstream\n{$stream}endstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($number = 1; $number <= count($objects); $number++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }
        $pdf .= 'trailer'."\n<< /Size ".(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    private function escape(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $encoded ?: $value);
    }
}
