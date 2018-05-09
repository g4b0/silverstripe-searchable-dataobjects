<?php

use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\ORM\Search\FulltextSearchable;

define('SEARCHABLE_DATAOBJECTS_DIR', basename(dirname(__FILE__)));

// Enable full text search. This is required by the module
FulltextSearchable::enable();

// Remove ContentControllerSearchExtension as it conflicts with the CustomSearch extension
ContentController::remove_extension("SilverStripe\\CMS\\Search\\ContentControllerSearchExtension");
