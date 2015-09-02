<?php
/**
 * Created by PhpStorm.
 * User: neochief
 * Date: 9/2/15
 * Time: 5:24 PM
 */

namespace LawPages\Renderer;

interface RendererInterface
{
    /**
     * @param $text
     * @return string
     */
    public function render($text);
    
}