<?php
/**
 * Test SearchableDataObject
 */
class SearchableDataObjectTest extends SapphireTest
{
    protected $extraDataObjects = array(
        'TestDataObject',
    );

    protected static $fixture_file = 'SearchableDataObjectTest.yml';

    protected $requiredExtensions = array(
        'Page'           => array('SearchableDataObject'),
        'TestDataObject' => array('SearchableDataObject'),
    );

    /**
     * Get search results for given query
     * @param  string        $query Search text
     * @return PaginatedList        List of linked objects
     */
    private function getSearchResults($query)
    {
        $page = $this->objFromFixture('Page', 'homepage');
        $request = new SS_HTTPRequest('GET', $page->URLSegment, ['Search' => $query]);
        return Page_Controller::create($page)->getSearchResults($request);
    }

    /**
     * Get count of search results for query
     * @param  string $query Search text
     * @return int           Count of search results
     */
    private function getSearchResultsCount($query)
    {
        return $this->getSearchResults($query)->getTotalItems();
    }

    // Tests

    public function testHasTestDataObjectTable()
    {
        $this->assertTrue(DB::get_schema()->hasTable('TestDataObject'));
    }

    public function testHasSearchableDataObjectsTable()
    {
        $schema = DB::get_schema();

        $this->assertTrue($schema->hasTable('SearchableDataObjects'));
    }

    public function testHasSearchableDataObjectsIndex()
    {
        if (!(DB::get_conn() instanceof MySQLDatabase)) {
            $this->markTestSkipped('MySQL only');
        }

        $schema = DB::get_schema();

        // ensure custom index exists
        $indexList = $schema->indexList('SearchableDataObjects');
        $this->assertArrayHasKey('Title', $indexList);
    }

    public function testMySQLTableIsMyISAM()
    {
        if (!(DB::getConn() instanceof MySQLDatabase)) {
            $this->markTestSkipped('MySQL only');
        }

        $result = DB::query(sprintf(
            'SHOW TABLE STATUS WHERE "Name" = \'%s\'',
            'SearchableDataObjects'
        ))->first();
        $this->assertEquals('MyISAM', $result['Engine']);
    }

    public function testSearchForDataObject()
    {
        if (!CustomSearch::isFulltextSupported()) {
            $this->markTestSkipped('Fulltext not supported');
        }

        $object = $this->objFromFixture('TestDataObject', 'test1');
        $this->assertEquals(1, $this->getSearchResultsCount($object->Subtitle));
    }

    public function testDraftPageNotSearchable()
    {
        if (!CustomSearch::isFulltextSupported()) {
            $this->markTestSkipped('Fulltext not supported');
        }

        $page = $this->objFromFixture('Page', 'homepage');
        $this->assertEquals(0, $this->getSearchResultsCount($page->Title));
    }

    public function testPublishedPageSearchable()
    {
        if (!CustomSearch::isFulltextSupported()) {
            $this->markTestSkipped('Fulltext not supported');
        }

        $page = $this->objFromFixture('Page', 'homepage');

        // publish the page
        $page->publish("Stage", "Live");
        $this->assertEquals(1, $this->getSearchResultsCount($page->Title));
    }

    public function testDraftPageChangeNotSearchable()
    {
        if (!CustomSearch::isFulltextSupported()) {
            $this->markTestSkipped('Fulltext not supported');
        }

        $page = $this->objFromFixture('Page', 'homepage');

        // publish the page
        $page->publish("Stage", "Live");

        $this->assertEquals(1, $this->getSearchResultsCount($page->Title));

        // make a new change and check
        $page->Title = 'Draft change';
        $page->write();

        // recheck with draft title
        $this->assertEquals(0, $this->getSearchResultsCount($page->Title));
    }

    public function testDeleteDataObject()
    {
        if (!CustomSearch::isFulltextSupported()) {
            $this->markTestSkipped('Fulltext not supported');
        }

        $object = $this->objFromFixture('TestDataObject', 'test1');
        $subtitle = $object->Subtitle;

        $this->assertEquals(1, $this->getSearchResultsCount($subtitle));

        // delete item
        $object->delete();
        $this->assertEquals(0, $this->getSearchResultsCount($subtitle));
    }
}
