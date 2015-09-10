<?php

namespace LawPages\Renderer;

class FixedWidthLawRenderer implements RendererInterface
{
    public function render($text)
    {
        $text = $this->removeWrapper($text);
        $text = $this->escapeAsterix($text);
        $text = $this->addBreaks($text);
        $text = $this->removeAnchors($text);
        $text = $this->removeComments($text);
        $text = $this->removeHead($text);
        $text = $this->removeFooter($text);
        $text = $this->fixParagraphs($text);
        $text = $this->formatHeaders($text);
        $text = $this->formatSubHeaders($text);

        return $text;
    }

    protected function removeWrapper($text)
    {
        $text = preg_replace('|<p></p>\n<div style="width:550px;max-width:100%;margin:0 auto"><pre>[\s\n\r]*|', '',
            $text);
        $text = preg_replace('%[\s\n\r]*</pre></div>\n<p></p>$%', '', $text);
        $text = preg_replace('%</pre></div>\n<p></p>%', '', $text);

        return $text;
    }
    
    protected function escapeAsterix($text)
    {
        $text = preg_replace('%\*%', '\*', $text);
        return $text;
    }

    protected function addBreaks($text)
    {
        $text = preg_replace('%<br>%', "\n\n", $text);

        return $text;
    }

    protected function removeAnchors($text)
    {
        $text = preg_replace('%<a name="[on][0-9]+"></a>%', '', $text);
        return $text;
    }

    protected function removeComments($text)
    {
        $text = preg_replace('%<i>\s*{[\s\S]*?}[\s\n\r]*</i>\n%', '', $text);
        return $text;
    }

    protected function removeHead($text)
    {
        $text = preg_replace('%\s*<img src="http://zakonst.rada.gov.ua/images/gerb.gif" title="Герб України">[\s\n\r\x{00a0}]+%u',
            '', $text);

        $zakon = str_replace('   ', '\s+', 'З А К О Н   У К Р А Ї Н И');
        $zakon = str_replace(' ', '\x{00a0}+', $zakon);

        $text = preg_replace('%' .
            '<b>[\s\n\r\x{00a0}]+(' . $zakon . '|КОНСТИТУЦІЯ УКРАЇНИ)[\s\n\r\x{00a0}]+</b>[\s\n\r\x{00a0}]*' .
            '(<b>[\s\S]*?</b>[ \x{00a0}]*\n)?' .
            '(<i>[\s\S]*?</i>[ \x{00a0}]*\n)?' .

            '%u', '', $text);
        return $text;
    }

    protected function removeFooter($text)
    {
        $text = preg_replace_callback('%\n( Президент України|[\s\x{00a0}]+' . preg_quote('\*\*\*', '%') . ') [\s\S]*$%u', function($matches){
            $footer = $matches[0];
            $footer = preg_replace('&\n\n&', "\n", $footer);
            
            // Format footer with stars (see 254к/96-вр).
            $footer = str_replace('\*\*\*', '**\*\*\***', $footer);
            $footer = preg_replace_callback('&<b>([\s\S]+)</b>&', function($matches) {
                $result = $matches[1];
                $result = preg_replace('&^\s*&m', '## ', $result);
                $result = preg_replace('&\s*$&m', '', $result);
                return $result;
            }, $footer);
            
            return "&FOOTER&" . preg_replace('&\n\s*&', "\n&FBREAK&", $footer);
        }, $text);
        
        return $text;
    }

    protected function fixParagraphs($text)
    {
        $text = preg_replace_callback('%(^|\n)(     )%', function($matches){
            $result = $matches[1] . "&BREAK&" . $matches[2];
            return $result;
        } , $text);

        $text = preg_replace('%\n%', "", $text);
        $text = preg_replace('%([^^])&BREAK&(     )?%', "$1\n\n&BREAK&$2", $text);

        $text = preg_replace_callback('%&BREAK&(     )(.*?)\n%', function($matches){
            $result = preg_replace('&[ \x{00a0}][ \x{00a0}]+&', ' ', $matches[2]);
            $result = "&BREAK&" . $matches[1] . $result . "\n";
            return $result;
        } , $text);
        $text = preg_replace('%&BREAK&(     )?%', "", $text);

        $text = preg_replace('%&FBREAK&?%', "\n", $text);
        $text = preg_replace('%&FOOTER&?%', "\n", $text);
        

        return $text;
    }

    protected function formatHeaders($text)
    {
        $text = preg_replace('%(^|\n)\s*<b>Р\x{00a0}о\x{00a0}з\x{00a0}д\x{00a0}і\x{00a0}л ([XVI]+?)</b>\s*\n\s*([x{00a0} АБВГҐДЕЄЖЗИІЇЙКЛМНОПРСТУФХЦЧШЩЬЮЯыЫъЪ]+)%u', "$1\n\n## Розділ $2\n## $3", $text);

        return $text;
    }

    protected function formatSubHeaders($text)
    {
        $text = preg_replace('%<b>(.*?)</b>%', '**$1**', $text);
        return $text;
    }

}