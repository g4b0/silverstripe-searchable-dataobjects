<?php

/**
 * Extension to provide a search interface when applied to ContentController
 *
 * @package cms
 * @subpackage search
 */
class CustomSearch extends Extension
{

    /**
     * @var int the number of items for each page, used for pagination
     */
    private static $items_per_page = 10;

    /**
     * either 'this' for the current page (owner) or a page / controller, e.g. 'SearchPage'
     * @var string
     */
    private static $search_controller = 'SearchPage';

    private static $allowed_actions = array(
            'SearchForm',
            'results',
    );

    /**
     * Site search form
     */
    public function SearchForm()
    {
        $form = new SearchForm($this->getControllerForSearchForm(), 'SearchForm', $this->getSearchFields(), $this->getSearchActions());
        return $form;
    }

    /**
     * generates the fields for the SearchForm
     * @uses updateSearchFields
     * @return FieldList
     */
    public function getSearchFields()
    {
        $searchText = _t('SearchForm.SEARCH', 'Search');

        if ($this->owner->request && $this->owner->request->getVar('Search')) {
            $searchText = $this->owner->request->getVar('Search');
        }

        $fields = new FieldList(
            new TextField('Search', false, $searchText)
        );

        $this->owner->extend('updateSearchFields', $fields);

        return $fields;
    }

    /**
     * generates the actions of the SearchForm
     * @uses updateSearchActions
     * @return FieldList
     */
    public function getSearchActions()
    {
        $actions = new FieldList(
            new FormAction('results', _t('SearchForm.GO', 'Go'))
        );

        $this->owner->extend('updateSearchActions', $actions);

        return $actions;
    }

    /**
     *
     * @return ContentController
     */
    public function getControllerForSearchForm()
    {
        $controllerName = Config::inst()->get('CustomSearch', 'search_controller');

        if ($controllerName == 'this') {
            return $this->owner;
        }

        if (class_exists($controllerName)) {
            $obj = Object::create($controllerName);

            if ($obj instanceof SiteTree && $page = $controllerName::get()->first()) {
                return ModelAsController::controller_for($page);
            }

            if ($obj instanceof Controller) {
                return $obj;
            }
        }

        //fallback:
        //@todo: throw notice
        return $this->owner;
    }

    /**
     * Process and render search results.
     *
     * @param SS_HTTPRequest $request Request generated for this action
     * @param array $data The raw request data submitted by user
     * @param SearchForm $form The form instance that was submitted
     */
    public function getSearchResults($request, $data = [], $form = null)
    {
        // check that Fulltext is enabled
        if (!self::isFulltextSupported()) {
            // empty result if not
            return PaginatedList::create(ArrayList::create());
        }

        $conn = DB::getConn();
        $list = new ArrayList();

        // get search query
        $q = (isset($data['Search'])) ? $data['Search'] : $request->getVar('Search');

        $input = $conn->addslashes($q);

        if ($conn instanceof SQLite3Database) {
            // query using SQLite FTS
            $query = "SELECT * FROM \"SearchableDataObjects\" WHERE \"SearchableDataObjects\" MATCH '$input'";
        } else {
            // query using MySQL Fulltext
            $query = "SELECT * FROM \"SearchableDataObjects\" WHERE MATCH (\"Title\", \"Content\") AGAINST ('$input' IN NATURAL LANGUAGE MODE)";
        }

        $results = DB::query($query);

        foreach ($results as $row) {
            $do = DataObject::get_by_id($row['ClassName'], $row['ID']);

            /*
             * Check that we have been returned a valid DataObject, using the
             * ClassName and ID stored in the SortableDataObject DB table, to
             * prevent PHP notice:
             *
             *      [Strict Notice] Creating default object from empty value
             *
             * caused when DataObject::get_by_id() returns false
             */
            if (is_object($do) && $do->exists()) {
                $do->Title = $row['Title'];
                $do->Content = $row['Content'];

                $list->push($do);
            }
        }

        $pageLength = Config::inst()->get('CustomSearch', 'items_per_page');
        $ret = new PaginatedList($list, $request);
        $ret->setPageLength($pageLength);

        return $ret;
    }

    public function results($data, $form, $request)
    {
        $data = array(
                'Results' => $this->getSearchResults($request, $data),
                'Query' => $form->getSearchQuery(),
                'Title' => _t('CustomSearch.SEARCHRESULTS', 'Risultati della ricerca')
        );

        return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
    }

    /**
     * Check if Fulltext search is supported
     * @return boolean True if supported
     */
    public static function isFulltextSupported()
    {
        $conn = DB::get_conn();

        if ($conn instanceof MySQLDatabase) {
            return true;
        }

        // check SQLite and enabled
        if ($conn instanceof SQLite3Database) {
            $checkOption = "sqlite_compileoption_used('IsFullTextInstalled')";
            $result = DB::query("SELECT $checkOption")->first();
            if (isset($result[$checkOption]) && $result[$checkOption]) {
                return true;
            }
        }

        return false;
    }
}
