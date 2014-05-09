<?php

/**
 * Extension to provide a search interface when applied to ContentController
 *
 * @package cms
 * @subpackage search
 */
class CustomSearch extends Extension {

	static $allowed_actions = array(
			'SearchForm',
			'results',
	);

	/**
	 * Site search form
	 */
	public function SearchForm() {
		
		$searchText = _t('SearchForm.SEARCH', 'Search');

		if ($this->owner->request && $this->owner->request->getVar('Search')) {
			$searchText = $this->owner->request->getVar('Search');
		}

		$fields = new FieldList(
						new TextField('Search', false, $searchText)
		);
		$actions = new FieldList(
						new FormAction('results', _t('SearchForm.GO', 'Go'))
		);
		$form = new SearchForm($this->owner, 'SearchForm', $fields, $actions);
		return $form;
	}

	/**
	 * Process and render search results.
	 *
	 * @param array $data The raw request data submitted by user
	 * @param SearchForm $form The form instance that was submitted
	 * @param SS_HTTPRequest $request Request generated for this action
	 */
	public function getSearchResults($request) {

		$list = new ArrayList();
						
		$v = $request->getVars();
		if (!isset($v["start"]))
			$v["start"] = 0;

		$q = $v["Search"];
		$s = $v["start"];
		
		$input = DB::getConn()->addslashes($q);
		$data = DB::query("SELECT ID, ClassName FROM SearchableDataObjects WHERE MATCH (Title, Content) AGAINST ('$input' IN NATURAL LANGUAGE MODE)");
		
		foreach ($data as $row) {
			
			$do = DataObject::get_by_id($row['ClassName'], $row['ID']);
			switch ($row['ClassName']) {
				case 'DoSoftware':
					$do->Title = $do->descrizione;
					$do->Content = strip_tags($do->descrizione_html);
					break;
				case 'DoProdotto':
					$do->Title = $do->descrizione;
					$do->Content = strip_tags($do->descrizione);
					break;
				default:
					break;
			}
			
			$pattern = '/(\[embed.*\])/si';
			$replacement = '';
			$do->Content = preg_replace($pattern, $replacement, $do->Content);

			$pattern = '/(\[.*?\])/si';
			$replacement = '';
			$do->Content = preg_replace($pattern, $replacement, $do->Content);					
						
			$list->push($do);
		}
			
		$ret = new PaginatedList($list);
		$ret->pageLength = 10;
		$ret->pageStart = $s;
		$ret->totalItems = $list->count();
		$ret->limitItems = 0;
		
		return $ret;
	}

	public function results($data, $form, $request) {
		
		$data = array(
				'Results' => $this->getSearchResults($request),
				'Query' => $form->getSearchQuery(),
				'Title' => _t('Search_results', 'Risultati della ricerca')
		);
		return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
	}

}
