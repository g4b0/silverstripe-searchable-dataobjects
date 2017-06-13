<?php

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
        $class = $do->class;
        DB::query("DELETE FROM \"SearchableDataObjects\" WHERE ID=$id AND ClassName='$class'");
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (in_array('Searchable', class_implements($this->owner->class))) {
            if ($this->owner->hasExtension('Versioned')) {
                $filterID = array('ID' => $this->owner->ID);
                $filter = $filterID + $this->owner->getSearchFilter();
                $do = Versioned::get_by_stage($this->owner->class, 'Live')->filter($filter)->first();
            } else {
                $filterID = "`{$this->findParentClass()}`.`ID`={$this->owner->ID}";
                $do = DataObject::get($this->owner->class, $filterID, false)->filter($this->owner->getSearchFilter())->first();
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
        $connection = DB::getConn();
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
            array('value' => '"Title", "Content"', 'type' => 'fulltext')
        );
    }

    /**
     * Recursive function to find the parent class of the current data object
     */
    private function findParentClass($class = null)
    {
        if (is_null($class)) {
            $class = $this->owner->class;
        }

        $parent = singleton($class)->parentClass();

        return $parent === 'DataObject' ? $class : $this->findParentClass($parent);
    }
}
