<?php

use LawPages\Renderer\FixedWidthLawRenderer;

class FixedWidthLawRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FixedWidthLawRenderer
     */
    private $renderer;

    public function setUp()
    {
        parent::setUp();
        $this->renderer = new FixedWidthLawRenderer();
    }

    /**
     * @dataProvider wrappers
     */
    public function testRemoveWrapper($data, $expected)
    {
        $this->assertEquals($expected, $this->renderer->removeWrapper($data));
    }

    public function wrappers()
    {
        return [
            [
                <<<HERE
<p></p>
<div style="width:550px;max-width:100%;margin:0 auto"><pre>                             1</pre></div>
<p></p>
HERE
                ,
                <<<HERE
1

HERE
            ],
            [
                <<<HERE
<p></p>
<div style="width:550px;max-width:100%;margin:0 auto"><pre>                             1
2</pre></div>
<p></p><p></p>
<div style="width:550px;max-width:100%;margin:0 auto"><pre></pre></div>
<p></p><p></p>
<div style="width:550px;max-width:100%;margin:0 auto"><pre>3
4</pre></div>
<p></p>
HERE
                ,
                <<<HERE
1
2

3
4

HERE
            ],
        ];
    }

    public function testEscapeAsterisk()
    {
        $this->assertEquals('\*\*\*', $this->renderer->escapeAsterisk('***'));
    }

    public function testAddBreaks()
    {
        $this->assertEquals("\n123\n", $this->renderer->addBreaks('<br>123<br>'));
        $this->assertEquals("\n123\n", $this->renderer->addBreaks('<br >123<br>'));
        $this->assertEquals("\n123\n", $this->renderer->addBreaks('<br/>123<br>'));
        $this->assertEquals("\n123\n", $this->renderer->addBreaks('<br />123<br>'));
    }
    
    public function testRemoveAnchors()
    {
        $this->assertEquals('<br>' . "\n", $this->renderer->removeAnchors('<a name="o10"></a><br>' . "\n" . '<a name="n4996"></a>'));
    }

    /**
     * @dataProvider comments
     */
    public function testRemoveComments($data, $expected)
    {
        $this->assertEquals($expected, $this->renderer->removeComments($data));
    }

    public function comments()
    {
        return [
            [
                <<<HERE
<i> ( Із змінами, внесеними згідно із Законом 
   N 2222-IV ( <a href="/laws/show/2222-15/ed20060101" target="_blank">2222-15</a> ) від 08.12.2004, ВВР, 2005, N 2, ст.44 ) 

</i>
<a name="o7"></a><i>      { Щодо змін додатково див. Наказ Міністерства охорони 
                                                   здоров'я 
        N 297 ( <span style="color:#000">v0297282-09</span> ) та N 2222-IV ( <a href="/laws/show/2222-15/ed20060101" target="_blank">2222-15</a> ) від 08.12.2004, ВВР від 05.05.2009 } 
</i>
HERE
                ,
                <<<HERE
<a name="o7"></a>
HERE
            ],
            [
                <<<HERE
затримані чи заарештовані.
<i>(  Офіційне  тлумачення  положень частини третьої статті 80 див. в 

Рішенні  Конституційного  Суду  N  9-рп/99  (  <a href="/laws/show/v009p710-99/ed20060101" target="_blank">v009p710-99</a>  )  від 

27.10.99 ) 

(  Офіційне  тлумачення  положень частин першої, третьої статті 80 

див.  в  Рішенні Конституційного Суду N 12-рп/2003 ( <a href="/laws/show/v012p710-03/ed20060101" target="_blank">v012p710-03</a> ) 

від 26.06.2003 ) 

</i>     <b>Стаття    81.</b>
HERE
                ,
                <<<HERE
затримані чи заарештовані.
     <b>Стаття    81.</b>
HERE
            ],
            [
                <<<HERE
(  Офіційне 
тлумачення  положення  частини  четвертої  статті 5 див. в Рішенні 
Конституційного Суду N 6-рп/2005 ( <a href="/laws/show/v006p710-05/ed20060101" target="_blank">v006p710-05</a> ) від 05.10.2005 ) 

Ради  України. { Частина перша статті 77 в 
редакції  Закону  N  2222-IV  ( <a href="/laws/show/2222-15/ed20060101" target="_blank">2222-15</a> ) від 08.12.2004 - набирає 
чинності  з  дня  набуття  повноважень  Верховною  Радою  України, 
обраною у 2006 році }
може двічі змінювати одні й ті самі положення Конституції України. 
(  Офіційне  тлумачення  положення  частини другої статті 158 див. 
в  Рішенні  Конституційного  Суду  N  8-рп/98  ( <a href="/laws/show/v008p710-98/ed20060101" target="_blank">v008p710-98</a> ) від 
09.06.98 ) 

6)     <b>Стаття 159.</b> Законопроект про  внесення  змін  до  Конституції 
HERE
                ,
                <<<HERE

Ради  України. 
може двічі змінювати одні й ті самі положення Конституції України. 

6)     <b>Стаття 159.</b> Законопроект про  внесення  змін  до  Конституції 
HERE
            ],
            [
                <<<HERE
test
<i>           { Закон N 2222-IV ( <a href="/laws/show/2222-15/ed20100930" target="_blank">2222-15</a> ) від 08.12.2004 
    "Про внесення змін до Конституції України" визнано таким, 
    що не відповідає Конституції України (є неконституційним), 
         у зв'язку з порушенням конституційної процедури 
  його розгляду та прийняття. Див. Рішення Конституційного Суду 
          N 20-рп/2010 ( <a href="/laws/show/v020p710-10/ed20100930" target="_blank">v020p710-10</a> ) від 30.09.2010 } 

</i>
test
HERE
                ,
                <<<HERE
test
test
HERE
            ],
            [
                <<<HERE
( Відомості Верховної Ради України (ВВР), 1996, N 30, ст. 141 ) 1991 року  (  <a href="/laws/show/1427-12/ed20060101" target="_blank">1427-12</a>  ),  схваленим ( q ( 111) w ( <span>222</span> ) e ( 333 ) ) r
HERE
                ,
                <<<HERE
( Відомості Верховної Ради України (ВВР), 1996, N 30, ст. 141 ) 1991 року  (  <a href="/laws/show/1427-12/ed20060101" target="_blank">1427-12</a>  ),  схваленим  r
HERE
            ],
        ];
    }

    /**
     * @dataProvider heads
     */
    public function testFormatHead($data, $expected)
    {
        $this->assertEquals($expected, $this->renderer->formatHead($data));
    }

    public function heads()
    {
        return [
            [
                <<<HERE
                             <img src="http://zakonst.rada.gov.ua/images/ussr.gif" title="Герб УРСР">                             
<b>                             У К А З 
             ПРЕЗИДІЇ ВЕРХОВНОЇ РАДИ УКРАЇНСЬКОЇ РСР 
</b>
<b>                 Про скликання позачергової сесії 
                  Верховної Ради Української РСР 
</b>
<i>     ( Відомості Верховної Ради УРСР (ВВР), 1991, N 36, ст. 468 ) 

</i>
HERE
                ,
                <<<HERE
![Герб УРСР](http://zakonst.rada.gov.ua/images/ussr.gif)

# УКАЗ
# ПРЕЗИДІЇ ВЕРХОВНОЇ РАДИ УКРАЇНСЬКОЇ РСР

# Про скликання позачергової сесії
# Верховної Ради Української РСР

_( Відомості Верховної Ради УРСР (ВВР), 1991, N 36, ст. 468 )_


HERE
            ],
            [
                <<<HERE
                             <img src="http://zakonst.rada.gov.ua/images/gerb.gif" title="Герб України">                             
<b>                       КОНСТИТУЦІЯ УКРАЇНИ 
</b>
<i>     ( Відомості Верховної Ради України (ВВР), 1996, N 30, ст. 141 ) 
</i>
HERE
                ,
                <<<HERE
![Герб України](http://zakonst.rada.gov.ua/images/gerb.gif)

# КОНСТИТУЦІЯ УКРАЇНИ

_( Відомості Верховної Ради України (ВВР), 1996, N 30, ст. 141 )_


HERE
            ],
            [
                <<<HERE
                             <img src="http://zakonst.rada.gov.ua/images/gerb.gif" title="Герб України">                             
<b>              МІНІСТЕРСТВО ОХОРОНИ ЗДОРОВ'Я УКРАЇНИ 
</b>
<b>                            Н А К А З
</b>
<b>      Про внесення змін до Інструкції про порядок відкриття 
   та використання рахунків у національній та іноземній валюті

</b>
123
HERE
                ,
                <<<HERE
![Герб України](http://zakonst.rada.gov.ua/images/gerb.gif)

# МІНІСТЕРСТВО ОХОРОНИ ЗДОРОВ'Я УКРАЇНИ

# НАКАЗ

# Про внесення змін до Інструкції про порядок відкриття
# та використання рахунків у національній та іноземній валюті

123
HERE
            ]
        ];
    }
}