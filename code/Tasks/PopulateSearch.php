<?php

/**
 * Ricrea la tabella di ricerca ad ogni esecuzione, e la popola con i dati
 * prelevati dai DataObject
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 23-Apr-2014
 */
class PopulateSearch extends BuildTask {
	
	/**
	 * DB initalization
	 */
	private function clearTable() {
		DB::query("DROP TABLE IF EXISTS SearchableDataObjects");
		DB::query("CREATE TABLE IF NOT EXISTS SearchableDataObjects (
													ID int(10) unsigned NOT NULL,
													ClassName varchar(255) NOT NULL,
													Title varchar(255) NOT NULL,
													Content text NOT NULL,
													PageID integer NOT NULL DEFAULT 0,
													PRIMARY KEY(ID, ClassName)
												) ENGINE=MyISAM");
		DB::query("ALTER TABLE SearchableDataObjects ADD FULLTEXT (`Title` ,`Content`)");
	}
	
	/**
	 * Refactor the DataObject in order to match with SearchableDataObjects table
	 * and insert it into the database
	 * @param DataObject $do
	 */
	public static function insert(DataObject $do) {
		// Title
		$Title = '';
		$first = true;
		foreach($do->getTitleFields() as $field) {
			(!$first) ? $Title .= ' ' : $first = false;
			$Title .= Purifier::PurifyTXT($do->$field);
		}
		echo "$Title\n";
		$Title = DB::getConn()->addslashes($Title);

		// Content
		$Content = '';
		$first = true;
		foreach($do->getContentFields() as $field) {
			(!$first) ? $Content .= ' ' : $first = false;
			$Content .= ' ' . Purifier::PurifyTXT($do->$field);
		}
		echo "$Content\n";
		$Content = DB::getConn()->addslashes($Content);

		DB::query("INSERT INTO SearchableDataObjects(ID,  ClassName, Title, Content) VALUES ("
						. "$do->ID, '$do->ClassName', '$Title', '$Content')"
						. "ON DUPLICATE KEY UPDATE Title='$Title', Content='$Content'");
	}
	
	/**
	 * Clean page's title and content and insert it into SearchableDataObjects
	 * @param Page $p
	 */
	public static function insertPage(Page $p) {
		
		$Title = DB::getConn()->addslashes($p->Title);
		$Content = Purifier::PurifyTXT($p->Content);
		$Content = Purifier::RemoveEmbed($Content);
		$Content = DB::getConn()->addslashes($Content);
		DB::query("INSERT INTO SearchableDataObjects(ID,  ClassName, Title, Content) VALUES ("
						. "$p->ID, '$p->ClassName', '$Title', '$Content')"
						. "ON DUPLICATE KEY UPDATE Title='$Title', Content='$Content'");
			
	}
	
	/**
	 * Task run
	 * @param type $request
	 */
	public function run($request) {
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
		$searchables = ClassInfo::implementorsOf('Searchable');
		foreach ($searchables as $class) {
			// Filter
			
			$dos = $class::get()->filter($class::getSearchFilter());
			
			$dos = $class::get()->filter(array());
			foreach ($dos as $do) {
				self::insert($do);
			}
		}
		
	}
	
}
