<?php

namespace g4b0\SearchableDataObjects;

use \Page;
use g4b0\SearchableDataObjects\Tasks\PopulateSearch;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;

/**
 * SearchableDataObject - extension that let the DO to auto update the search table
 * after a write
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 12-May-2014
 */
class SearchableDataObject extends DataExtension
{

    private function deleteDo(DataObject $do)
    {
        $id = $do->ID;
        $class = $do->getClassName();
        DB::query("DELETE FROM \"SearchableDataObjects\" WHERE ID=$id AND ClassName='$class'");
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (in_array('g4b0\SearchableDataObjects\Searchable', class_implements($this->owner->getClassName()))) {
            if ($this->owner->hasExtension('Versioned')) {
                $filterID = array('ID' => $this->owner->ID);
                $filter = $filterID + $this->owner->getSearchFilter();
                $do = Versioned::get_by_stage($this->owner->getClassName(), 'Live')->filter($filter)->first();
            } else {
                $filterID = "`{$this->findParentTable()}`.`ID`={$this->owner->ID}";
                $do = DataObject::get($this->owner->getClassName(), $filterID, false)->filter($this->owner->getSearchFilter())->first();
            }

            if ($do) {
                PopulateSearch::insert($do);
            } else {
                $this->deleteDo($this->owner);
            }
        } elseif ($this->owner instanceof Page) { // Page is versioned but usually doesn't implement Searchable
            $page = Versioned::get_by_stage('Page', 'Live')->filter(array(
                'ID' => $this->owner->ID,
                'ShowInSearch' => 1,
            ))->first();
            if ($page) {
                PopulateSearch::insertPage($page);
            } else {
                $this->deleteDo($this->owner);
            }
        }
    }

    /**
     * Remove the entry from the search table before deleting it
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        $this->deleteDo($this->owner);
    }

  /**
   * Check and create the required table during dev/build
   */
    public function augmentDatabase()
    {
        $connection = DB::get_conn();
        $schema = DB::get_schema();
        $isMySQL = ($connection->getDatabaseServer() === 'mysql');
        $unsigned = ($isMySQL) ? 'unsigned' : '';
        $extraOptions = ($isMySQL) ? ' ENGINE=MyISAM' : '';

        // construct query to create table with custom primary key
        $sql = join(' ', [
            'CREATE TABLE IF NOT EXISTS "SearchableDataObjects" (',
                '"ID" int(10) ' . $unsigned . ' NOT NULL,',
                '"ClassName" ' . $schema->varchar(['precision' => 255]) . ',',
                '"Title" ' . $schema->varchar(['precision' => 255]) . ' NOT NULL,',
                '"Content" ' . $schema->text([]) . ' NOT NULL,',
                '"PageID" ' . $schema->int(['precision' => 11, 'null' => 'NOT NULL', 'default' => 0]) . ',',
                'PRIMARY KEY("ID", "ClassName")',
            ')',
            $extraOptions,
        ]);

        // add table
        DB::query($sql);

        // add search index requirement
        DB::require_index(
            'SearchableDataObjects',
            'Title',
            [
                'columns' => [ 'Title', 'Content'],
                'type' => 'fulltext'
            ]
        );
    }

    /**
     * Recursive function to find the parent table of the current data object
     */
    private function findParentTable($class = null)
    {
        if (is_null($class)) {
            $class = $this->owner->getClassName();
        }
 
        $parent = get_parent_class($class);

        // Get the table name of the class
        $tableName = singleton($class)->baseTable();

        return $parent === 'SilverStripe\ORM\DataObject' ? $tableName : $this->findParentTable($parent);
    }
}
