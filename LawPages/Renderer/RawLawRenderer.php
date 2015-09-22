<?php

namespace LawPages\Renderer;

use LawGrabber\Laws\Revision;

class RawLawRenderer extends BaseRenderer
{
    public function render($text, Revision $revision)
    {
        $text = $this->removeWrapper($text);
        $text = $this->formatLinks($text, $revision);
        $text = $this->fixTypography($text);

        return $text;
    }

    public function removeWrapper($text)
    {
        $text = preg_replace('|<p></p>\n<div style="width:550px;max-width:100%;margin:0 auto"><pre> *([\s\S]*?)</pre></div>\n<p></p>|u',
            "$1\n", $text);

        $text = preg_replace('%<link href="/laws/file/util/0/u_text.css" rel="stylesheet" type="text/css"></link>%u', '', $text);
        $text = preg_replace('%<link rel="stylesheet" type="text/css" href="/laws/file/util/0/u_text.css">%u', '', $text);

        $text = preg_replace('%^[\n\s]*<span class="rvts0">%u', '', $text);
        $text = preg_replace('%[\n\s]*</span>[\n\s]*$%u', '', $text);
        $text = preg_replace('%</span>\n<span class="rvts0">%u', '', $text);

        $text = preg_replace('%([^\n])(<a name="[on][0-9]+"></a>)%u', "$1\n$2", $text);
        
        return $text;
    }
}