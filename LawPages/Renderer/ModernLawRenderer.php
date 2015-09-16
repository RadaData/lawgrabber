<?php

namespace LawPages\Renderer;

use LawGrabber\Laws\Revision;

class ModernLawRenderer extends BaseRenderer
{
    public function render($text, Revision $revision)
    {
        $text = $revision->text;
        $text = $this->removeWrapper($text);
        $text = $this->removeAnchors($text);
        $text = $this->removeComments($text);
        $text = $this->formatHeaders($text);
        $text = $this->formatTables($text);
        $text = $this->formatSubHeaders($text);
        $text = $this->removeParagraphsHTMl($text);
        $text = $this->formatLinks($text, $revision);
        $text = $this->fixTypography($text);

        return $text;
    }

    public function removeWrapper($text)
    {
        $text = preg_replace('%<link href="/laws/file/util/0/u_text.css" rel="stylesheet" type="text/css"></link>%', '', $text);
        $text = preg_replace('%<link rel="stylesheet" type="text/css" href="/laws/file/util/0/u_text.css">%', '', $text);

        $text = preg_replace('%^[\n\s]*<span class="rvts0">%', '', $text);
        $text = preg_replace('%[\n\s]*</span>[\n\s]*$%', '', $text);
        $text = preg_replace('%</span>\n<span class="rvts0">%', '', $text);
        
        return $text;
    }

    public function removeAnchors($text)
    {
        $text = preg_replace('%<a name="[on][0-9]+"></a>[\s\n\r]*%', '', $text);
        return $text;
    }

    public function removeComments($text)
    {
        $text = preg_replace('%(<p class="rvps[0-9]+">)?(<span class="rvts4[68]">)?{[\s\S]*?}(</span>)?(</p>)?\n*%', '', $text);
        return $text;
    }

    public function formatHeaders($text)
    {
        $text = preg_replace_callback('%<p class="rvps(?:6|7)">([\s\S]*?)</p>%', function($matches){
            $text = $matches[1];
            $text = preg_replace('%<span class="rvts23">\s*(.*?)\s*</span>%', '# $1', $text);
            $text = preg_replace('%<span class="rvts15">\s*(.*?)\s*</span>%', '## $1', $text);
            $text = preg_replace('%<br>#%', '#', $text);
            $text = preg_replace('%<a class="rvts103" href=".*?">(.*?)</a>## *%', '## $1', $text);
            $text = "\n" . $text . "\n";
            return $text;
        }, $text);

        return $text;
    }

    public function formatTables($text)
    {
        $text = preg_replace_callback('%<div class="rvps(?:14|8)">\n*<table.*?>([\s\S]*?)</table>\n*</div>%', function($matches) {
            $table = '<table>' . $matches[1] . '</table>';

            $table = preg_replace('%(?:<p class="rvps(?:1|4|14)">)?<span class="rvts(?:9|15|23)">\s*(.*?)\s*</span>(?:</p>)?%', '<b class="table-header">$1</b>', $table);

            $table = preg_replace('%<b class="table-header"><br></b>%', '', $table);
            

            // rvps14 - rvps14
            // rvps14 - rvps11
            // rvps4 - rvps15

            $config = array(
                'clean' => true,
                'output-html' => true,
                'show-body-only' => true,
                'wrap' => 0,
                'indent' => true,
            );
            $tidy = new \Tidy;
            $tidy->parseString($table, $config, 'utf8');
            $tidy->cleanRepair();

            return $tidy . "\n";

        }, $text);


        return $text;
    }

    public function formatSubHeaders($text)
    {
        $text = preg_replace('%\*%', '\*', $text);
        $text = preg_replace('%<span class="rvts(?:9|15|23)">\s*(.*?)(\s*)</span>%', '**$1**$2', $text);
        $text = preg_replace('%<span class="rvts37"><span style="font-size:0px">-</span>(.*?)</span>%', '-$1', $text);
        $text = preg_replace('%<br>\*\*%', '**', $text);

        return $text;
    }

    public function removeParagraphsHTMl($text)
    {
        $text = preg_replace('%<p class="rvps2">\s*([\s\S]*?)\s*</p>%', "$1\n", $text);
        $text = preg_replace('%<p class="rvps12">\s*([\s\S]*?)\s*</p>%', "$1", $text);
        $text = preg_replace('%<p>\s*</p>%', "\n", $text);
        $text = preg_replace('%<p class="rvps8"><br></p>%', "\n", $text);

        return $text;
    }

}