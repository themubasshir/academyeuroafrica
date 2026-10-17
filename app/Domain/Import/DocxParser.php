<?php

namespace App\Domain\Import;

use DOMDocument;
use DOMElement;
use ZipArchive;

/**
 * Extracts structured course content from a .docx file.
 *
 * The client's module packages use a consistent heading convention:
 *   Heading 1  "MODULE N — TITLE"            → module boundary
 *   Heading 1  "N.M — LESSON TITLE"          → lesson boundary
 *   Heading 2  "<Letter>. <Section>"         → per-lesson sections
 *                                             (A. Trainer Script, B. Participant
 *                                              Learning Text, C. PowerPoint…,
 *                                              …Lesson Quiz, …Answer Key)
 *
 * Lists / Normal paragraphs carry the prose. We only need a paragraph stream +
 * its outline level, so we read word/document.xml directly — no Composer dep.
 */
class DocxParser
{
    /** @return array<int, array{level:int, style:string, text:string}> */
    public function paragraphs(string $path): array
    {
        $zip = new ZipArchive;
        abort_unless($zip->open($path) === true, 422, "Cannot open {$path}");
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        abort_if($xml === false, 422, "No word/document.xml in {$path}");

        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

        $out = [];
        foreach ($dom->getElementsByTagNameNS($ns, 'p') as $p) {
            /** @var DOMElement $p */
            $text = '';
            foreach ($p->getElementsByTagNameNS($ns, 't') as $t) {
                $text .= $t->textContent;
            }
            // Some client DOCX XML contains invalid byte sequences; stripping
            // them keeps MySQL/JSON from rejecting otherwise valid content.
            $text = trim(preg_replace('/\s+/u', ' ', mb_convert_encoding($text, 'UTF-8', 'UTF-8')));
            if ($text === '') {
                continue;
            }

            $level = 0;
            $style = 'Normal';
            $pPr = $p->getElementsByTagNameNS($ns, 'pPr')->item(0);
            if ($pPr instanceof DOMElement) {
                $pStyle = $pPr->getElementsByTagNameNS($ns, 'pStyle')->item(0);
                if ($pStyle instanceof DOMElement) {
                    $style = $pStyle->getAttributeNS($ns, 'val') ?: 'Normal';
                    if (preg_match('/^(Heading|heading)\s*(\d)/i', $style, $m)) {
                        $level = (int) $m[2];
                    } elseif (preg_match('/^Title$/i', $style)) {
                        $level = 1;
                    }
                }
            }
            $out[] = ['level' => $level, 'style' => $style, 'text' => $text];
        }

        return $out;
    }
}
