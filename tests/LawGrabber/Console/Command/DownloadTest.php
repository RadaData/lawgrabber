<?php

use LawGrabber\Laws\Issuer;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawGrabber\Laws\State;
use LawGrabber\Laws\Type;

class DownloadCommandTest extends TestCase
{

    /**
     * @var LawGrabber\Console\Commands\Download
     */
    protected $obj = null;

    public function setUp()
    {
        parent::setUp();

        $this->obj = $this->app->make('command.lawgrabber.download');

        $this->db()->table('law_issuers')->truncate();
        $this->db()->table('law_types')->truncate();
        $this->db()->table('law_revisions')->truncate();
        $this->db()->table('jobs')->truncate();
        $this->db()->table('laws')->truncate();

        $this->db()->table('issuers')->insert([
            'id' => 'o1',
            'name' => 'Верховна Рада України',
            'full_name' => '123',
            'group_name' => '123',
            'website' => '123',
            'url' => '123',
            'international' => 0,
        ]);
        Type::create([
            'id' => 'o1',
            'name' => 'Закон',
        ])->save();
        Type::create([
            'id' => 'o2',
            'name' => 'Конституція',
        ])->save();
        State::create([
            'id' => 'o5',
            'name' => 'Чинний',
        ])->save();
    }

    public function tearDown()
    {
        unset($this->obj);
    }

    public function testDownloadCard()
    {
        $this->assertTrue(downloader()->isDownloaded('/laws/card/254к/96-вр'));

        Law::firstOrCreate(['id' => '254к/96-вр']);
        $law = $this->obj->downloadCard('254к/96-вр');

        $this->assertEquals(file_get_contents(base_path() . '/tests/fixtures/partials/254к/96-вр/card.txt'), $law->card);
        $this->assertEquals('1996-06-28', $law->date);
        $this->assertEquals('Конституція України', $law->title);
        $this->assertEquals('Чинний', $law->state);
        $this->assertEquals(['Верховна Рада України'], $law->getIssuers());
        $this->assertEquals(['Закон', 'Конституція'], $law->getTypes());
        $this->assertEquals(70, $law->revisions()->count());
        $this->assertEquals([
            'id' => 70,
            'date'         => '2014-05-15',
            'law_id'       => '254к/96-вр',
            'state'        => 0,
            'text'         => '',
            'text_updated' => 0,
            'comment'      => 'Тлумачення, підстава - <a href="/laws/show/v005p710-14" target="_blank">v005p710-14</a>',
            'status'       => Revision::NEEDS_UPDATE,
        ], $law->getActiveRevision()->toArray());


        $this->assertTrue(downloader()->isDownloaded('/laws/card/2952-17'));

        Law::firstOrCreate(['id' => '2952-17']);
        $law = $this->obj->downloadCard('2952-17');

        $this->assertEquals(2, $law->revisions()->count());
        $this->assertEquals([
            'id' => 71,
            'date'         => '2011-02-01',
            'law_id'       => '2952-17',
            'state'        => 0,
            'text'         => '',
            'text_updated' => 0,
            'comment'      => 'Прийняття',
            'status'       => Revision::NEEDS_UPDATE,
        ], $law->getActiveRevision()->toArray());
        $this->assertEquals($this->redownloadCardJobsCount(), 1);

        $this->db()->table('jobs')->truncate();

        $law = $this->obj->downloadCard('254к/96-вр');
        $this->assertEquals($this->redownloadCardJobsCount(), 0);
        $law = $this->obj->downloadCard('2952-17');
        $this->assertEquals($this->redownloadCardJobsCount(), 0);
    }

    private function redownloadCardJobsCount()
    {
        return $this->db()->table('jobs')->where('method', 'downloadCard')->count();
    }

    public function testDownloadRevision()
    {
        $this->assertTrue(downloader()->isDownloaded('/laws/card/254к/96-вр'));
        $this->assertTrue(downloader()->isDownloaded('/laws/show/254к/96-вр/ed20140515/page'));
        $this->assertTrue(downloader()->isDownloaded('/laws/show/254к/96-вр/ed20140515/page2'));
        $this->assertTrue(downloader()->isDownloaded('/laws/show/254к/96-вр/ed20140515/page3'));
        $this->assertTrue(downloader()->isDownloaded('/laws/show/254к/96-вр/ed20140515/page4'));

        Law::firstOrCreate(['id' => '254к/96-вр']);
        $law = $this->obj->downloadCard('254к/96-вр');
        $revision = $this->obj->downloadRevision('254к/96-вр', '2014-05-15');

        $text = file_get_contents(base_path() . '/tests/fixtures/partials/254к/96-вр/text.txt');
        $this->assertEquals($revision->text, $text);
        $this->assertEquals($law->active_revision()->first()->text, $text);
        $this->assertEquals($revision->status, Revision::UP_TO_DATE);


        $this->assertTrue(downloader()->isDownloaded('/laws/card/2952-17'));
        $this->assertTrue(downloader()->isDownloaded('/laws/show/2952-17/ed20110201/page'));
        Law::firstOrCreate(['id' => '2952-17']);
        $law = $this->obj->downloadCard('2952-17');
        $revision = $this->obj->downloadRevision('2952-17', '2011-02-01');

        $text = file_get_contents(base_path() . '/tests/fixtures/partials/2952-17/text.txt');
        $this->assertEquals($revision->text, $text);
        $this->assertEquals($law->active_revision()->first()->text, $text);
        $this->assertEquals($revision->status, Revision::UP_TO_DATE);
    }
}