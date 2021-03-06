<?php

namespace LawPages\Renderer;

use LawGrabber\Laws\Revision;

class FixedWidthLawRenderer extends WithChangesRenderer
{
    public function render($text, Revision $revision)
    {
        $text = $this->removeWrapper($text);
        $text = $this->escapeAsterisk($text);
        $text = $this->addBreaks($text);
        $text = $this->removeAnchors($text);
        $text = $this->removeComments($text);
        $text = $this->formatHead($text);
        $text = $this->formatHeaders($text);
        $text = $this->formatSubHeaders($text);
        $text = $this->formatLinks($text, $revision);
        $text = $this->fixParagraphs($text);
        $text = $this->fixTypography($text);

        return $text;
    }

    public function removeWrapper($text)
    {
        $text = preg_replace('|<p></p>\n<div style="width:550px;max-width:100%;margin:0 auto"><pre> *([\s\S]*?)</pre></div>\n<p></p>|u',
            "$1\n", $text);

        return $text;
    }

    public function escapeAsterisk($text)
    {
        $text = preg_replace('%\*%u', '\*', $text);
        return $text;
    }

    public function addBreaks($text)
    {
        $text = preg_replace('%([^\n])(<a name="[on][0-9]+"></a>)%u', "$1\n$2", $text);
        $text = preg_replace('%<br */?>%u', "\n", $text);

        return $text;
    }

    public function removeAnchors($text)
    {
        $text = preg_replace('%<a name="[on][0-9]+"></a>%u', '', $text);
        return $text;
    }

    public function removeComments($text)
    {
        $text = preg_replace_callback('%<i>\s*(?:(\n)?(?:[{(])(?:[^{}()<]*(?:\([^()<]*\))?[^{}()<]*)(?:\(\s*<(?:a|span)[^<]*?</(?:a|span)>\s*\)(?:[^{}()<]*(?:\([^()<]*\))?[^{}()<]*))+(?:[^})<]*?)(?:[})])(?:\s*(\n))?)+\s*</i>\n?%u',
            function ($matches) {
                $a = 1;
            }, $text);
        $text = preg_replace_callback('%(\n)?(?:[{(])(?:[^{}()<]*(?:\([^()<]*\))?[^{}()<]*)(?:\(\s*<(?:a|span)[^<]*?</(?:a|span)>\s*\)(?:[^{}()<]*(?:\([^()<]*\))?[^{}()<]*))+(?:[^})<]*?)(?:[})])(?:\s*(\n))?%u',
            function ($matches) {
                if (array_get($matches, 1, '') && array_get($matches, 2, '')) {
                    return "\n\n";
                }
                else {
                    return array_get($matches, 1, '') . array_get($matches, 2, '');
                }
            }, $text);

        return $text;
    }

    function formatTitleLine($line, $h = 1)
    {
        $line = preg_replace('%(<b>|</b>)%u', '', $line);
        $line = preg_replace('%^\s*%mu', '', $line);
        $line = preg_replace('%\s*$%mu', '', $line);
        $line = preg_replace('%\n\n\n%u', '\n', $line);
        
        $line = preg_replace('%\x{00a0}%u', '', $line);
        $line = preg_replace('%(?<= |^)(\S) (\S) (\S)(?: (\S))?(?: (\S))?(?: (\S))?(?: (\S))?(?: (\S))?(?: (\S))?%u', '$1$2$3$4$5$6$7$8$9', $line);

        $line = preg_replace('%^%mu', str_repeat('#', $h) . ' ', $line);
        return $line;
    }

    function formatSubtitleLine($line)
    {
        $line = trim($line);
        $line = preg_replace('%\n\n\n%u', '\n', $line);
        $line = '_' . $line . '_';
        return $line;
    }
    
    public function formatHead($text)
    {
        $text = preg_replace_callback('%\s*<img src="(.*?)"( title="(.*?)")?>[\s\n\r\x{00a0}]+\n'
            . '(?:<b>[\s\n\r\x{00a0}]+([\s\S]*?)[\s\n\r\x{00a0}]+</b>\n)'
            . '(?:<b>[\s\n\r\x{00a0}]+([\s\S]*?)[\s\n\r\x{00a0}]+</b>\n)?'
            . '(?:<b>[\s\n\r\x{00a0}]+([\s\S]*?)[\s\n\r\x{00a0}]+</b>\n)?'
            . '(?:<b>[\s\n\r\x{00a0}]+([\s\S]*?)[\s\n\r\x{00a0}]+</b>\n)?'
            . '(?:<b>[\s\n\r\x{00a0}]+([\s\S]*?)[\s\n\r\x{00a0}]+</b>\n)?'
            . '(?:\n*<i>[\s\n\r\x{00a0}]+([\s\S]*?)[\s\n\r\x{00a0}]+</i>\n?)?%u',
            function ($matches) {
                $parts[] = '![' . $matches[3] . '](' . $matches[1] . ')' ;
                $parts[] = $this->formatTitleLine($matches[4]);
                for ($i = 5; $i < 9; $i++) {
                    $parts[] = (isset($matches[$i]) && $matches[$i]) ? ($this->formatTitleLine($matches[$i])) : '';
                }
                $parts[] = isset($matches[9]) ? ($this->formatSubtitleLine($matches[9])) : '';
                $parts = array_filter($parts);
                $output = implode("\n\n", $parts) . "\n\n";
                return $output;
            }, $text);

        return $text;
    }

    public function formatHeaders($text)
    {
        $text = preg_replace('%(^|\n)\s*<b>Р\x{00a0}о\x{00a0}з\x{00a0}д\x{00a0}і\x{00a0}л ([XVI]+?)</b>\s*\n\s*([x{00a0} АБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ,\.\-_\']+)%u',
            "$1\n\n## Розділ $2\n## $3", $text);

        return $text;
    }

    public function formatSubHeaders($text)
    {
        $text = preg_replace('%<b>Стаття\s+([0-9]+\.)</b>%u', '**Стаття $1**', $text);
        $text = preg_replace_callback('%<b>([\s\S]*?)</b>%u', function($matches) {
            $text = $matches[1];
            if (strpos($text, "\n") !== FALSE) {
                return $this->formatTitleLine($text, 3) . "\n";
            }
            else {
                return '**' . $text . '**';
            }
        }, $text);
        $text = preg_replace('%<i>\s*([\s\S]*?)\s*</i>%u', '_$1_', $text);
        return $text;
    }

    public function fixParagraphs($text)
    {
        // 'м.Київ' is added to not screw up the law headers (see z0512-00).
        
        $text = preg_replace_callback('%(^|\n)(     )(?!м.Київ)([^ ][\s\S]*?)(?=(?:\n |\n\n?#|\n\n?--|\n__|\n\*\*|$))%u', function($matches){
            $text = $matches[3];
            $text = preg_replace('%(?<! ) {2,10}(?! )%mu', " ", $text);
            $text = preg_replace('%\n%u', "", $text);

            $result = $matches[1] . $text . "\n";
            return $result;
        }, $text);
        $text = preg_replace('% *$%mu', '', $text);

        return $text;
    }
}