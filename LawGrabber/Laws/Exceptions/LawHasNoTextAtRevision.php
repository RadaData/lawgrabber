<?php

namespace LawGrabber\Laws\Exceptions;

use LawGrabber\Laws\Law;
use LawGrabber\Laws\Revision;

class LawHasNoTextAtRevision extends \Exception
{

    /**
     * LawHasNoTextAtRevision constructor.
     *
     * @param Law      $law
     * @param Revision $revision
     */
    public function __construct(Law $law, Revision $revision) {
        $this->message = 'Law #' . $law->id . ' has no text prior revision #' . $revision->date;
    }
}