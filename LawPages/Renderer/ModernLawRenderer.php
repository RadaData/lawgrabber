<?php

namespace LawPages\Renderer;

use League\HTMLToMarkdown\HtmlConverter;

class ModernLawRenderer implements RendererInterface
{
    public function render($text)
    {
        $text = $this->removeWrapper($text);
        $text = $this->removeAnchors($text);
        $text = $this->removeComments($text);
        $text = $this->removeHead($text);
        $text = $this->formatHeaders($text);
        $text = $this->formatSubHeaders($text);
        $text = $this->removeParagraphsHTMl($text);

        return $text;
    }
    
    private function removeWrapper($text)
    {
        $text = preg_replace('%<link href="/laws/file/util/0/u_text.css" rel="stylesheet" type="text/css"></link>%', '', $text);
        $text = preg_replace('%<link rel="stylesheet" type="text/css" href="/laws/file/util/0/u_text.css">%', '', $text);

        $text = preg_replace('%^[\n\s]*<span class="rvts0">%', '', $text);
        $text = preg_replace('%[\n\s]*</span>[\n\s]*$%', '', $text);
        $text = preg_replace('%</span>\n<span class="rvts0">%', '', $text);
        
        return $text;
    }

    private function removeHead($text)
    {
        $text = preg_replace('%<div class="rvps(14|8)">[\s\S]+?</div>\n*%', '', $text);
        $text = preg_replace('%<p class="rvps6">[\s\S]+?</p>\n*%', '', $text);
        $text = preg_replace('%<p class="rvps7"><span class="rvts44">[\s\S]+?</span></p>%', '', $text);

        return $text;
    }

    private function removeAnchors($text)
    {
        $text = preg_replace('%<a name="n[0-9]+"></a>[\s\n\r]*%', '', $text);
        return $text;
    }

    private function removeComments($text)
    {
        $text = preg_replace('%(<p class="rvps[0-9]+">)?(<span class="rvts46">)?{[\s\S]*?}(</span>)?(</p>)?\n%', '', $text);
        return $text;
    }

    private function formatHeaders($text)
    {
        $text = preg_replace('%<span class="rvts15">\s*(.*?)\s*</span>%', '## $1', $text);
        $text = preg_replace('%<br>#%', '', $text);

        $text = preg_replace('%<p class="rvps7">#([\s\S]*?)</p>%', "\n\n\n#$1\n", $text);
        
        return $text;
    }

    private function formatSubHeaders($text)
    {
        $text = preg_replace('%<span class="rvts9">\s*(.*?)\s*</span>%', '[$1]', $text);
        $text = preg_replace('%<span class="rvts37"><span style="font-size:0px">-</span>(.*?)</span>%', '-$1', $text);
        return $text;
    }

    private function removeParagraphsHTMl($text)
    {
        $text = preg_replace('%<p class="rvps2">\s*(.*?)\s*</p>%', "$1\n", $text);

        return $text;
    }


}