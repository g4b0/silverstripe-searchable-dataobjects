<?php
/**
 * Test CustomSearch
 */
class CustomSearchTest extends FunctionalTest
{

    protected $extraDataObjects = array(
        'TestDataObject',
    );

    protected static $fixture_file = 'SearchableDataObjectTest.yml';

    protected $requiredExtensions = array(
        'Page'           => array('SearchableDataObject'),
        'TestDataObject' => array('SearchableDataObject'),
    );

    public function setUp()
    {
        parent::setUp();

        // suppress themes
        Config::inst()->remove('SSViewer', 'theme');

        // set up search page
        $page = $this->objFromFixture('SearchPage', 'searchpage');
        $page->write();
        $page->publish('Stage', 'Live');
    }

    private function getPageController()
    {
        $page = $this->objFromFixture('SearchPage', 'searchpage');
        return ContentController::create($page);
    }

    private function getSearchResults($query)
    {
        $page = $this->objFromFixture('SearchPage', 'searchpage');
        $request = new SS_HTTPRequest('GET', $page->URLSegment, ['Search' => $query]);
        return $this->getPageController()->getSearchResults($request);
    }

    public function testSearchForm()
    {
        $controller = $this->getPageController();

        $this->assertInstanceOf('SearchForm', $form = $controller->SearchForm());
    }

    public function testSearchFormResults()
    {
        if (!CustomSearch::isFulltextSupported()) {
            $this->markTestSkipped('Fulltext not supported');
        }

        // publish page for test
        $homepage = $this->objFromFixture('Page', 'homepage');
        $homepage->publish("Stage", "Live");

        $searchpage = $this->objFromFixture('SearchPage', 'searchpage');

        $page = $this->get($searchpage->URLSegment);

        // verify search page
        $this->assertEquals(200, $page->getStatusCode());
        $this->assertExactMatchBySelector('h1', ['Search']);

        $submit = $this->submitForm('SearchForm_SearchForm', 'action_results', array(
            'Search' => $homepage->Title,
        ));

        // check homepage in results
        $this->assertPartialMatchBySelector('.search-result-link', [$homepage->Title, $homepage->MenuTitle]);
    }
}
