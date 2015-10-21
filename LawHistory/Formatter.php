<?php

namespace LawHistory;

use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use Illuminate\Console\Command;

class Formatter {

    /**
     * @var bool
     */
    private $is_raw;

    /**
     * @var Command
     */
    private $command;

    /**
     * GitHistory constructor.
     * @param Command $console
     */
    public function __construct(Command $console, $is_raw = true)
    {
        $this->is_raw = $is_raw;
        $this->command = $console;
    }

    public function getRevisionText(Revision $revision)
    {
        $text = view('lawpages::law')->with([
            'revision' => $revision,
            'raw' => $this->is_raw
        ]);
        return $text;
    }

    public function createCommitMessage($branch, array $commit)
    {
        $lines = [];
        foreach ($commit as $revision) {
            $output = $revision->law_id . '@' . $revision->date;
            if ($revision->comment) {
                $output .= ': ' . $this->formatRevisionComment($revision, false);
            }
            $lines[] = $output;
        }
        return implode("\n", $lines);
    }

    public function formatRevisionComment(Revision $revision, $add_links = false)
    {
        $comment = $revision->comment;

        $comment = preg_replace_callback('%<a href="(.*?)" target="_blank">(.*?)</a>%', function($matches) use ($revision, $add_links){
            $url = urldecode($matches[1]);
            $title = $matches[2];
            if ($add_links) {
                if (preg_match('%/laws/(.*?)(?:$|/ed|#|\?)%', $url, $matches)) {
                    $law_id = $matches[1];
                    $url = $this->getLawURL($law_id, '/RadaData/zakon');
                }
                return "[$title]($url)";
            }
            else {
                return $title;
            }
        }, $comment);

        $comment = preg_replace_callback('%([\s\S]+?)(, підстава.*)?$%', function($matches) use ($revision){
            $statuses = explode("\n", array_get($matches, 1, ''));
            $reason = array_get($matches, 2, '');

            $law = $revision->getLaw();
            $type = $law->types()->get()->first();
            if (!$type) {
                return $matches[0];
            }
            $title = $law->title;
            $type_name = $type->name;
            $type_name = mb_strtolower(mb_substr($type_name, 0, 1)) . mb_substr($type_name, 1, mb_strlen($type_name) - 1);

            $i = 0;
            foreach ($statuses as &$status) {
                $status = trim($status);
                
                if ($status == 'Прийняття') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Прийнята';
                            break;
                        case 'b':
                            $status = 'Прийнято';
                            break;
                        case 'b+':
                            $status = 'Прийняті';
                            break;
                        default:
                            $status = 'Прийнятий';
                            break;
                    }
                }
                if ($status == 'Ратифікація') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Ратифікована';
                            break;
                        case 'b':
                            $status = 'Ратифіковано';
                            break;
                        case 'b+':
                            $status = 'Ратифіковані';
                            break;
                        default:
                            $status = 'Ратифікований';
                            break;
                    }
                }
                if ($status == 'Скасування') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Скасована';
                            break;
                        case 'b':
                            $status = 'Скасовано';
                            break;
                        case 'b+':
                            $status = 'Скасовані';
                            break;
                        default:
                            $status = 'Скасований';
                            break;
                    }
                }
                if ($status == 'Затвердження') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Затверджена';
                            break;
                        case 'b':
                            $status = 'Затверджено';
                            break;
                        case 'b+':
                            $status = 'Затверджені';
                            break;
                        default:
                            $status = 'Затверджений';
                            break;
                    }
                }
                elseif (mb_strpos($status, 'Набрання чинності') !== FALSE) {
                    $status = preg_replace('| міжнародного договору|u', '', $status);
                    switch ($type->getRid()) {
                        case 'f':
                            $status = preg_replace('|Набрання чинності|u', 'Набрала чинності', $status);
                            break;
                        case 'b':
                            $status = preg_replace('|Набрання чинності|u', 'Набрало чинності', $status);
                            break;
                        case 'b+':
                            $status = preg_replace('|Набрання чинності|u', 'Набрали чинності', $status);
                            break;
                        default:
                            $status = preg_replace('|Набрання чинності|u', 'Набрав чинності', $status);
                            break;
                    }
                } elseif ($status == 'Введення в дію') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Введена в дію';
                            break;
                        case 'b':
                            $status = 'Введено в дію';
                            break;
                        case 'b+':
                            $status = 'Введені в дію';
                            break;
                        default:
                            $status = 'Введений в дію';
                            break;
                    }
                } elseif ($status == 'Припинення дії') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Припинила дію';
                            break;
                        case 'b':
                            $status = 'Припинило дію';
                            break;
                        case 'b+':
                            $status = 'Припинили дію';
                            break;
                        default:
                            $status = 'Припинив дію';
                            break;
                    }
                } elseif ($status == 'Зупинення дії') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Зупинила дію';
                            break;
                        case 'b':
                            $status = 'Зупинило дію';
                            break;
                        case 'b+':
                            $status = 'Зупинили дію';
                            break;
                        default:
                            $status = 'Зупинив дію';
                            break;
                    }
                } elseif ($status == 'Відновлення дії') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Відновила дію';
                            break;
                        case 'b':
                            $status = 'Відновило дію';
                            break;
                        case 'b+':
                            $status = 'Відновили дію';
                            break;
                        default:
                            $status = 'Відновив дію';
                            break;
                    }
                } elseif ($status == 'Не набрав чинності') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Не набрала чинності';
                            break;
                        case 'b':
                            $status = 'Не набрало чинності';
                            break;
                        case 'b+':
                            $status = 'Не набрали чинності';
                            break;
                        default:
                            $status = 'Не набрав чинності';
                            break;
                    }
                } elseif ($status == 'Підписання') {
                    switch ($type->getRid()) {
                        case 'f':
                            $status = 'Підписана';
                            break;
                        case 'b':
                            $status = 'Підписано';
                            break;
                        case 'b+':
                            $status = 'Підписані';
                            break;
                        default:
                            $status = 'Підписаний';
                            break;
                    }
                } elseif ($status == 'Редакція') {
                    $status = 'Додано нову редакцію в';
                } elseif ($status == 'Тлумачення') {
                    $status = 'Додано нове тлумачення в';
                } elseif ($status == 'Приєднання' && $type == 'конвенція') {
                    $status = 'Приєднання до';
                    $type = 'конвенції';
                }

                if ($i > 0) {
                    $status = mb_strtolower($status);
                }
                $i++;
            }

            $last_status = array_pop($statuses);
            $status = $statuses ? implode(', ', $statuses) . ' та ' . $last_status : $last_status;

            $comment = $status . ' ' . $type_name . ' "' . $title . '"' . $reason;
            $comment = preg_replace('|україн|u', 'Україн', $comment);

            return $comment;
        }, $comment);

        return $comment;
    }

    public function createPRTitle($branch, array $commit)
    {
        $parts = [];
        foreach ($commit as $revision) {
            $parts[] = $revision->law_id . '@' . $revision->date;
        }
        $output = implode(', ', $parts);

        $main_revision = reset($commit);
        $output .= ': ' . $this->formatRevisionComment($main_revision, false);;

        return $output;
    }

    public function createPRBody($branch, array $commit)
    {
        $lines = [];
        foreach ($commit as $revision) {
            $output = '[' . $revision->law_id . '](' . $this->getLawURL($revision->law_id, '/RadaData/zakon') . ')';
            if ($revision->comment) {
                $output .= ': ' . $this->formatRevisionComment($revision, true);
            }
            $lines[] = $output;
        }
        return implode("\n", $lines);
    }

    public function getLawURL($law_id, $base = '', $raw = false)
    {
        $law = Law::find($law_id);
        if (!$law) {
            return rtrim($base, '/') . '/' . $law_id;
        }
        $issuers = $law->issuers()->get()->all();
        $first_issuer = reset($issuers);
        $prefix = $this->is_raw ? 'laws' : $first_issuer->group_name . '/' . $first_issuer->name ;
        $filename = $prefix . '/' . $law_id . '.md';
        return rtrim($base, '/') . '/' . $filename;
    }
}