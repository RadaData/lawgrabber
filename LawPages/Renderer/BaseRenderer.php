<?php
/**
 * Created by PhpStorm.
 * User: neochief
 * Date: 9/2/15
 * Time: 5:24 PM
 */

namespace LawPages\Renderer;

use LawGrabber\Laws\Revision;

abstract class BaseRenderer
{
    /**
     * @param string $text
     * @param Revision $revision
     * @return string
     */
    abstract public function render($text, Revision $revision);

    public function formatLinks($text, Revision $revision)
    {
        $text = preg_replace('%<a (?:class="(?:rvts96|rvts99)" )?href="(.*?)(?:/ed[0-9]+)?(?:/paran[0-9]+)?(#n[0-9]+)?"(?: target="_blank")?>(.*?)</a>%', '<a href="$1.md$2">$3</a>', $text);

        return $text;
    }

    public function fixTypography($text)
    {
        $text = preg_replace('%&#039;%', "'", $text);
        $text = preg_replace('%&rsquo;%', "'", $text);
        $text = preg_replace('%’%', "'", $text);
        $text = preg_replace('%Грунт%u', "Ґрунт", $text);
        $text = preg_replace('%грунт%u', "ґрунт", $text);
        
        return $text;
    }
}