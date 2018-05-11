<?php

namespace g4b0\SearchableDataObjects\Tasks;

use \Page;
use g4b0\HTMLPurifier\Purifier;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

/**
 * Ricrea la tabella di ricerca ad ogni esecuzione, e la popola con i dati
 * prelevati dai DataObject
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 23-Apr-2014
 */
class PopulateSearch extends BuildTask
{
    /** @var string Task title */
    protected $title = 'Populate Search';
    /** @var string Task description */
    protected $description = 'Re-create the search table at each run, and populate it with the data from the DataObject.';

    private static $segment = "PopulateSearch";

    /**
     * DB initalization
     */
    private function clearTable()
    {
        // truncate the table
        DB::query("TRUNCATE TABLE SearchableDataObjects");

        // get searchable classes
        $implementors = ClassInfo::implementorsOf('g4b0\SearchableDataObjects\Searchable');

        // perform requirements for searchable classes
        DB::get_schema()->schemaUpdate(function () use ($implementors) {
            foreach ($implementors as $implementor) {
                if (class_exists($implementor)) {
                    $searchable = singleton($implementor);
                    $searchable->requireTable();
                    // only needs to be done once
                    // break;
                }
            }
        });
        ClassInfo::reset_db_cache();
    }

    /**
     * Refactor the DataObject in order to match with SearchableDataObjects table
     * and insert it into the database
     * @param DataObject $do
     */
    public static function insert(DataObject $do)
    {
        // Title
        $Title = '';
        foreach ($do->getTitleFields() as $field) {
            $Title .= Purifier::PurifyTXT($do->$field). ' ';
        }

        // Content
        $Content = '';
        foreach ($do->getContentFields() as $field) {
            $Content .= Purifier::PurifyTXT($do->$field). ' ';
        }
        self::storeData($do->ID, $do->ClassName, trim($Title), trim($Content));
    }

    /**
     * Clean page's title and content and insert it into SearchableDataObjects
     * @param Page $p
     */
    public static function insertPage(Page $p)
    {
        $Content = Purifier::PurifyTXT($p->Content);
        $Content = Purifier::RemoveEmbed($Content);

        self::storeData($p->ID, $p->ClassName, $p->Title, $Content);
    }

    /**
     * Escape the data and store to the database
     * @param $id
     * @param $class_name
     * @param $title
     * @param $content
     */

    private static function storeData($id, $class_name, $title, $content)
    {
        // prepare the query ...
        $query = sprintf(
            'REPLACE INTO `SearchableDataObjects`
                (`ID`,  `ClassName`, `Title`, `Content`)
             VALUES
                (%1$d, \'%2$s\', \'%3$s\', \'%4$s\')',
            intval($id),
            Convert::raw2sql($class_name),
            Convert::raw2sql($title),
            Convert::raw2sql($content)
        );

        // run query ...
        DB::query($query);
    }


    /**
     * Task run
     * @param type $request
     */
    public function run($request)
    {
        $this->clearTable();

        /*
         * Page
         */
        $pages = Versioned::get_by_stage('Page', 'Live')->filter(array('ShowInSearch' => 1));
        foreach ($pages as $p) {
            self::insertPage($p);
        }

        /*
         * DataObjects
         */
        $searchables = ClassInfo::implementorsOf('g4b0\SearchableDataObjects\Searchable');
        foreach ($searchables as $class) {
            // Filter
            $dos = $class::get()
                ->filter($class::getSearchFilter());
            if (method_exists($class, 'getSearchFilterAny')) {
                $dos = $dos->filterAny($class::getSearchFilterAny());
            }
            if (method_exists($class, 'getSearchFilterByCallback')) {
                $dos = $dos->filterByCallback($class::getSearchFilterByCallback());
            }

            if ($dos->exists()) {
                $versionedCheck = $dos->first();

                if ($versionedCheck->hasExtension('Versioned')) {
                    $dos = Versioned::get_by_stage($class, 'Live')->filter($class::getSearchFilter());
                }

                foreach ($dos as $do) {

                    // Force the class to DataObject so we can pass descendants of DataObject
                    $do->setClassName('DataObject');

                    self::insert($do);
                }
            }
        }
    }
}
