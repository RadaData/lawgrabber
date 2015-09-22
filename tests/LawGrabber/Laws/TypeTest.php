<?php

use LawGrabber\Laws\Issuer;
use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;
use LawGrabber\Laws\State;
use LawGrabber\Laws\Type;

class TypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider types
     */
    public function testGetRid($name, $expected)
    {
        $type = new Type(['id' => 'fake', 'name' => $name]);
        $this->assertEquals($expected, $type->getRid());
    }
    
    public function types() {
        return [
            ['Питання-відповідь', 'f'],
            ['Інструкція', 'f'],
            ['Панно', 'b'],
            ['Зміни', 'b+'],
            ['Узагальнення судової практики', 'b+'],
            ['Форма України', 'f'],
            ['Закон', 'm'],
            ['Кодекс України', 'm'],
        ];
    }
}