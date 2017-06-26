<?php
/**
 * Test PopulateSearch
 */
class PopulateSearchTest extends SapphireTest
{

    protected $extraDataObjects = array(
        'TestDataObject',
    );

    protected static $fixture_file = 'SearchableDataObjectTest.yml';

    protected $requiredExtensions = array(
        'TestDataObject' => array('SearchableDataObject'),
    );

    private function getSearchResults($query)
    {
        $page = $this->objFromFixture('Page', 'homepage');
        $request = new SS_HTTPRequest('GET', $page->URLSegment, ['Search' => $query]);
        return Page_Controller::create($page)->getSearchResults($request);
    }

    private function getSearchResultsCount($query)
    {
        return $this->getSearchResults($query)->getTotalItems();
    }

    public function testRecreateSearchableDataObjectsTable()
    {
        if (!(DB::get_conn() instanceof MySQLDatabase)) {
            $this->markTestSkipped('MySQL only');
        }

        $schema = DB::get_schema();
        $page = $this->objFromFixture('Page', 'homepage');
        $object = $this->objFromFixture('TestDataObject', 'test1');

        $this->assertTrue($schema->hasTable('SearchableDataObjects'));

        // check records before rebuild
        $count = $this->getSearchResultsCount('');

        $this->assertEquals(1, $this->getSearchResultsCount($object->Subtitle));

        // check if draft page is searchable
        $this->assertEquals(0, $this->getSearchResultsCount($page->Title));

        // run repopulate task
        $request = new SS_HTTPRequest('GET', '');
        $task = PopulateSearch::create();
        $task->run($request);

        // ensure table recreated
        $this->assertTrue($schema->hasTable('SearchableDataObjects'));

        // ensure custom index recreated
        $indexList = $schema->indexList('SearchableDataObjects');
        $this->assertArrayHasKey('Title', $indexList);

        // recheck records
        $this->assertEquals($count, $this->getSearchResultsCount(''));
        $this->assertEquals(1, $this->getSearchResultsCount($object->Subtitle));
        $this->assertEquals(0, $this->getSearchResultsCount($page->Title));
    }
}
