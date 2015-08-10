<?php

use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;

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
    }

    public function tearDown()
    {
        unset($this->obj);
    }

    public function testDownloadCard()
    {
        Law::firstOrCreate(['id' => '254к/96-вр']);
        $law = $this->obj->downloadCard('254к/96-вр');

        $this->assertEquals(file_get_contents(base_path() . '/tests/fixtures/partials/254к/96-вр/card.txt'), $law->card);
        $this->assertEquals('Чинний', $law->state);
        $this->assertArraysEqual(['Верховна Рада України'], $law->getIssuers());
        $this->assertArraysEqual(['Конституція', 'Закон'], $law->getTypes());
        $this->assertEquals(70, $law->revisions()->count());
        $this->assertEquals($law->getActiveRevision()->toArray(), [
            'id' => 70,
            'date'         => '2014-05-15',
            'law_id'       => '254к/96-вр',
            'state'        => null,
            'text'         => '',
            'text_updated' => null,
            'comment'      => '<u>Тлумачення</u>, підстава - <a href="/laws/show/v005p710-14" target="_blank">v005p710-14</a>',
            'status'       => Revision::NEEDS_UPDATE,
        ]);

        Law::firstOrCreate(['id' => '2952-17']);
        $law = $this->obj->downloadCard('2952-17');

        $this->assertEquals(2, $law->revisions()->count());
        $this->assertEquals($law->getActiveRevision()->toArray(), [
            'id' => 71,
            'date'         => '2011-02-01',
            'law_id'       => '2952-17',
            'state'        => null,
            'text'         => '',
            'text_updated' => null,
            'comment'      => '<u>Прийняття</u>',
            'status'       => Revision::NEEDS_UPDATE,
        ]);
        $this->assertEquals($this->redownloadCardJobsCount(), 1);

        $this->db()->table('jobs')->truncate();

        $law = $this->obj->downloadCard('254к/96-вр');
        $this->assertEquals($this->redownloadCardJobsCount(), 0);
        $law = $this->obj->downloadCard('2952-17');
        $this->assertEquals($this->redownloadCardJobsCount(), 0);
    }

    private function redownloadCardJobsCount()
    {
        return $this->db()->table('jobs')->where('method', 'downloadCard')->where('parameters', json_encode([
            'id'          => '254к/96-вр',
            're_download' => true
        ]))->count();
    }

    public function testDownloadRevision()
    {
        Law::firstOrCreate(['id' => '254к/96-вр']);
        $law = $this->obj->downloadCard('254к/96-вр');
        $revision = $this->obj->downloadRevision('254к/96-вр', '2014-05-15');

        $text = file_get_contents(BASE_PATH . 'tests/fixtures/partials/254к/96-вр/text.txt');
        $this->assertEquals($revision->text, $text);
        $this->assertEquals($law->active_revision()->first()->text, $text);
        $this->assertEquals($revision->status, Revision::UP_TO_DATE);

        Law::firstOrCreate(['id' => '2952-17']);
        $law = $this->obj->downloadCard('2952-17');
        $revision = $this->obj->downloadRevision('2952-17', '2011-02-01');

        $text = file_get_contents(BASE_PATH . 'tests/fixtures/partials/2952-17/text.txt');
        $this->assertEquals($revision->text, $text);
        $this->assertEquals($law->active_revision()->first()->text, $text);
        $this->assertEquals($revision->status, Revision::UP_TO_DATE);
    }
}