<?php

namespace LawGrabber\Downloader\Exceptions;

class DocumentHasErrors extends ContentError
{
    public function __construct($error)
    {
        parent::__construct("Document has following error: '{$error}'");
    }
}