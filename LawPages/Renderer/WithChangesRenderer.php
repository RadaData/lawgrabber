<?php

namespace LawPages\Renderer;

use LawGrabber\Laws\Revision;
use LawGrabber\Laws\Law;

abstract class WithChangesRenderer extends BaseRenderer
{
    public function formatLinks($text, Revision $revision)
    {
        $text = preg_replace_callback('%<a (?:class="(?:rvts96|rvts99)" )?href="(.*?)(?:/ed[0-9]+)?(?:/paran[0-9]+)?(?:#n[0-9]+)?"(?: target="_blank")?>(.*?)</a>%',
            function ($matches) use ($revision) {
                $url = urldecode($matches[1]);
                $text = $matches[2];
                if (!$url || $url == '/laws/show/' . $revision->law_id) {
                    return $text;
                }
                else {
                    if (preg_match('%/laws/show/(.*?)(?:$|/ed|#|\?)%', $url, $matches)) {
                        $law_id = $matches[1];
                        $law = Law::find($law_id);
                        if ($law) {
                            $issuers = $law->issuers()->get()->all();
                            $first_issuer = reset($issuers);
                            $url = '/' . $first_issuer->group_name . '/' . $first_issuer->name . '/' . $law_id . '.md';
                        }
                    }

                    return "[$text]($url)";
                }
            }, $text);
        $text = preg_replace('%\( \[(.*?)\]\((.*?)\) \)%', "([$1]($2))", $text);

        return $text;
    }
}