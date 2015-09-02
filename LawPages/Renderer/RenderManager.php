<?php

namespace LawPages\Renderer;

class RenderManager {

    /**
     * @var string
     */
    private $text;

    /**
     * @var RendererInterface
     */
    private $renderer;

    /**
     * RenderManager constructor.
     * 
     * @param $text
     */
    public function __construct($text)
    {
        $this->text = $text;
        $this->renderer = $this->getRenderer();
    }
    
    /**
     * @return string
     */
    public function render()
    {
        return $this->renderer->render($this->text);
    }

    /**
     * @return RendererInterface
     */
    public function getRenderer() {
        // TODO Get list of available law types.
        return new ModernLawRenderer();
    }
}