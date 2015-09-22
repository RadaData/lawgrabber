<?php

namespace LawPages\Renderer;

use LawGrabber\Laws\Revision;

class RenderManager {

    /**
     * @var string
     */
    private $text;

    /**
     * @var Revision
     */
    private $revision;

    /**
     * @var BaseRenderer
     */
    private $renderer;

    /**
     * @var bool
     */
    private $is_raw;

    /**
     * RenderManager constructor.
     * 
     * @param $text
     */
    public function __construct($text, Revision $revision, $is_raw)
    {
        $this->text = $text;
        $this->revision = $revision;
        $this->is_raw = $is_raw;
        $this->renderer = $this->getRenderer();
    }
    
    /**
     * @return string
     */
    public function render()
    {
        return $this->renderer->render($this->text, $this->revision);
    }

    /**
     * @return BaseRenderer
     */
    public function getRenderer() {
        if ($this->is_raw) {
            return new RawLawRenderer();
        }
        else if (strpos($this->text, '<div style="width:550px;max-width:100%;margin:0 auto">') != false) {
            return new FixedWidthLawRenderer();
        }
        else {
            return new ModernLawRenderer();
        }
    }
}