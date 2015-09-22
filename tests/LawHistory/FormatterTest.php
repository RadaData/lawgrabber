<?php

use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawGrabber\Laws\Type;
use LawHistory\Console\Commands\History;
use LawHistory\Formatter;

class FormatterTest extends TestCase
{
    /**
     * @var Formatter
     */
    private $formatter;

    public function setUp()
    {
        parent::setUp();
        $this->formatter = new Formatter(new History());
        
        Type::create(['id' => '1', 'name' => 'Постанова']);
        Type::create(['id' => '2', 'name' => 'Кодекс України']);
        Type::create(['id' => '3', 'name' => 'Узагальнення судової практики']);
    }

    /**
     * @dataProvider commits
     */
    public function testCreateCommitMessage($revisions, $expected)
    {
        $laws = [];
        $commit = [];

        foreach ($revisions as $revision) {
            $law_id = $revision['law_id'];
            if (!isset($laws[$law_id])) {
                $laws[$law_id] = Law::create(['id' => $law_id]);
            }
            $commit[$law_id] = new Revision([
                'law_id' => $law_id,
                'date' => $revision['date'],
                'comment' => $revision['comment']
            ]);
        }
        $this->assertEquals($expected, $this->formatter->createCommitMessage('test', $commit));
    }

    public function commits()
    {
        return [
            [
                [
                    ['law_id' => '123', 'date' => '2000-01-01', 'comment' => 'Test Commit'],
                ],
                "123@2000-01-01: Test Commit"
            ],
            [
                [
                    ['law_id' => '123', 'date' => '2000-01-01', 'comment' => 'Test Commit'],
                    ['law_id' => '321', 'date' => '2000-01-01', 'comment' => 'Test Commit2']
                ],
                "123@2000-01-01: Test Commit\n321@2000-01-01: Test Commit2"
            ],
        ];
    }

    /**
     * @dataProvider revisions
     */
    public function testFormatRevisionComment($law_id, $law_type, $law_title, $date, $comment, $add_Links, $expected)
    {
        $law = Law::create(['id' => $law_id, 'title' => $law_title]);
        $law->setTypes([$law_type]);
        $revision = new Revision([
            'law_id' => $law_id,
            'date' => $date,
            'comment' => $comment
        ]);
        $this->assertEquals($expected, $this->formatter->formatRevisionComment($revision, $add_Links));
    }

    public function revisions()
    {
        return [
            [
                'law_id' => '123',
                'law_type' => 'Постанова',
                'law_title' => 'Про тести',
                'date' => '2000-01-01',
                'comment' => 'Набрання чинності',
                true,
                'Набрала чинності постанова "Про тести"'
            ],
            [
                'law_id' => '123',
                'law_type' => 'Узагальнення судової практики',
                'law_title' => 'Про тести',
                'date' => '2000-01-01',
                'comment' => 'Не набрав чинності, підстава <a href="/laws/show/111" target="_blank">222</a>, <a href="/laws/show/333" target="_blank">444</a>',
                true,
                'Не набрали чинності узагальнення судової практики "Про тести", підстава [222](/RadaData/zakon/111), [444](/RadaData/zakon/333)'
            ],
            [
                'law_id' => '123',
                'law_type' => 'Кодекс України',
                'law_title' => 'Про тести',
                'date' => '2000-01-01',
                'comment' => 'Редакція, підстава <a href="/laws/show/111" target="_blank">222</a>, <a href="/laws/show/333" target="_blank">444</a>',
                false,
                'Додано нову редакцію в кодекс України "Про тести", підстава 222, 444'
            ],
        ];
    }

}