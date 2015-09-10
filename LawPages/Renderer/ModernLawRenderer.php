<?php

namespace LawPages\Renderer;

class ModernLawRenderer implements RendererInterface
{
    public function render($text)
    {
        $text = $this->removeWrapper($text);
        $text = $this->removeAnchors($text);
        $text = $this->removeComments($text);
        $text = $this->removeHead($text);
        $text = $this->formatHeaders($text);
        $text = $this->formatTables($text);
        $text = $this->formatSubHeaders($text);
        $text = $this->removeParagraphsHTMl($text);
        $text = $this->formatLinks($text);

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
        $text = preg_replace_callback('%<div class="rvps(14|8)">[\s\S]+?</div>\n*%', function($matches) {
            $result = $matches[0];

            if (preg_match('%<table(?:.*?)><tbody>\n<tr(?:.*?)>\n<td(?:.*?)>\n([\s\S]*?)\n</td>\n<td(?:.*?)>\n([\s\S]*?)\n</td>\n</tr>\n<tr(?:.*?)>\n<td(?:.*?)>\n([\s\S]*?)\n</td>\n<td(?:.*?)>\n([\s\S]*?)\n</td>\n</tr>\n</tbody></table>%', $result, $m)) {
                for ($i = 1; $i <= 4; $i++) {
                    $m[$i] = strip_tags($m[$i]);
                }
                $result = "\n" .$m[1] . '          ' . $m[2] . "\n";
                $result .= $m[3] . '          ' . $m[4];
            }
            else {
                $result = '';
            }

            return $result;
        }, $text);
        
        //$text = preg_replace('%<p class="rvps6">[\s\S]+?</p>\n*%', '', $text);
        //$text = preg_replace('%<p class="rvps7"><span class="rvts44">[\s\S]+?</span></p>%', '', $text);

        return $text;
    }

    private function removeAnchors($text)
    {
        $text = preg_replace('%<a name="[on][0-9]+"></a>[\s\n\r]*%', '', $text);
        return $text;
    }

    private function removeComments($text)
    {
        $text = preg_replace('%(<p class="rvps[0-9]+">)?(<span class="rvts4[68]">)?{[\s\S]*?}(</span>)?(</p>)?\n*%', '', $text);
        return $text;
    }

    private function formatHeaders($text)
    {
        $text = preg_replace_callback('%<p class="rvps(?:6|7)">([\s\S]*?)</p>%', function($matches){
            $text = $matches[1];
            $text = preg_replace('%<span class="rvts23">\s*(.*?)\s*</span>%', '# $1', $text);
            $text = preg_replace('%<span class="rvts15">\s*(.*?)\s*</span>%', '## $1', $text);
            $text = preg_replace('%<br>#%', '#', $text);
            $text = "\n" . $text . "\n";
            return $text;
        }, $text);

        return $text;
    }

    private function formatTables($text)
    {
        $text = preg_replace_callback('%<div class="rvps8">\n*<table.*?>([\s\S]*?)</table>\n*</div>%', function($matches) {
            $table = '<table>' . $matches[1] . '</table>';

            $table = preg_replace('%<span class="rvts9">\s*(.*?)\s*</span>%', '<b>$1</b>', $table);

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

    private function formatSubHeaders($text)
    {
        $text = preg_replace('%\*%', '\*', $text);
        $text = preg_replace('%<span class="rvts9">\s*(.*?)(\s*)</span>%', '**$1**$2', $text);
        $text = preg_replace('%<span class="rvts37"><span style="font-size:0px">-</span>(.*?)</span>%', '-$1', $text);
        
        return $text;
    }

    private function removeParagraphsHTMl($text)
    {
        $text = preg_replace('%<p class="rvps2">\s*([\s\S]*?)\s*</p>%', "$1\n", $text);
        $text = preg_replace('%<p class="rvps12">\s*([\s\S]*?)\s*</p>%', "$1", $text);
        $text = preg_replace('%<p>\s*</p>%', "\n", $text);
        $text = preg_replace('%<p class="rvps8"><br></p>%', "\n", $text);

        return $text;
    }

    protected function formatLinks($text)
    {
        $text = preg_replace('%<a class="(?:rvts96|rvts99)" href="(.*?)(?:/paran[0-9]+)?(?:#n[0-9]+)?"(?: target="_blank")?>(.*?)</a>%', "[$2]($1)", $text);

        return $text;
    }
}