<?php

namespace App\Reporting;

final class DocxReportWriter
{
    public function write(array $quiz, array $report): string
    {
        $rows = '';
        $rows .= $this->row(['Student', 'Student number', 'Score', 'Release', 'Submitted'], true);
        foreach ($report['attempts'] as $attempt) {
            $rows .= $this->row([
                $attempt['studentName'],
                $attempt['studentNumber'] ?: 'Unavailable',
                $attempt['scoringStatus'] === 'scored' ? $attempt['earnedPoints'].' / '.$attempt['maximumPoints'] : $attempt['scoringStatus'],
                $attempt['releasedAt'] ? 'Released' : 'Withheld',
                $attempt['submittedAt'] ? date('Y-m-d H:i', strtotime($attempt['submittedAt'])) : 'Not submitted',
            ]);
        }

        $title = $this->xml($quiz['courseCode'].' - '.$quiz['title']);
        $order = $report['sort'] === 'score' ? 'Score (high to low), then student name' : 'Student name (A to Z)';
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .'<w:p><w:pPr><w:pStyle w:val="Title"/></w:pPr><w:r><w:t>MARKADO Quiz Results</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t>'.$title.'</w:t></w:r></w:p>'
            .'<w:p><w:r><w:t>Order: '.$this->xml($order).'</w:t></w:r></w:p>'
            .'<w:tbl><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4"/><w:left w:val="single" w:sz="4"/><w:bottom w:val="single" w:sz="4"/><w:right w:val="single" w:sz="4"/><w:insideH w:val="single" w:sz="4"/><w:insideV w:val="single" w:sz="4"/></w:tblBorders></w:tblPr>'
            .$rows.'</w:tbl><w:sectPr><w:pgSz w:w="12240" w:h="15840"/></w:sectPr></w:body></w:document>';

        $zip = new StoredZipWriter;
        $zip->add('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>');
        $zip->add('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>');
        $zip->add('word/document.xml', $document);

        return $zip->finish();
    }

    private function row(array $cells, bool $heading = false): string
    {
        return '<w:tr>'.collect($cells)->map(function (string $cell) use ($heading): string {
            $properties = $heading ? '<w:rPr><w:b/></w:rPr>' : '';

            return '<w:tc><w:p><w:r>'.$properties.'<w:t xml:space="preserve">'.$this->xml($cell).'</w:t></w:r></w:p></w:tc>';
        })->implode('').'</w:tr>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
