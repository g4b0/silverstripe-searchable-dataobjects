<?php

/**
 * SearchableDevBuild
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 14-Sep-2015
 */
class SearchableDevBuild extends DevBuildController {
  
    private static $url_handlers = array(
        '' => 'build'
    );

    private static $allowed_actions = array(
        'build'
    );

    public function build($request) {
        parent::build($request);


        if(!Director::is_cli()) {
          $renderer = DebugView::create();
          $renderer->writeHeader();
          $renderer->writeInfo("SearchableDataObject", Director::absoluteBaseURL());
          echo "<div class=\"build\">";
        }

        DB::query("CREATE TABLE IF NOT EXISTS SearchableDataObjects (
													ID int(10) unsigned NOT NULL,
													ClassName varchar(255) NOT NULL,
													Title varchar(255) NOT NULL,
													Content text NOT NULL,
													PageID integer NOT NULL DEFAULT 0,
													PRIMARY KEY(ID, ClassName)
												) ENGINE=MyISAM");
        DB::query("ALTER TABLE SearchableDataObjects ADD FULLTEXT (`Title` ,`Content`)");

        echo 'table created!' . PHP_EOL;

        if(!Director::is_cli()) {
          echo "</div>";
          $renderer->writeFooter();
        }
    }
}