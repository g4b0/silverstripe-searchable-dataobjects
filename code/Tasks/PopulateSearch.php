<?php

/**
 * Ricrea la tabella di ricerca ad ogni esecuzione, e la popola con i dati
 * prelevati dai DataObject
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 23-Apr-2014
 */
class PopulateSearch extends BuildTask {
	
	private function clearTable() {
		DB::query("DROP TABLE IF EXISTS SearchableDataObjects");
		DB::query("CREATE TABLE IF NOT EXISTS SearchableDataObjects (
													ID int(10) unsigned NOT NULL,
													ClassName varchar(255) NOT NULL,
													Title varchar(255) NOT NULL,
													Content text NOT NULL,
													PageID integer NOT NULL DEFAULT 0										
												) ENGINE=MyISAM");
		DB::query("ALTER TABLE SearchableDataObjects ADD FULLTEXT (`Title` ,`Content`)");
	}
	
	public function run($request) {
		$this->clearTable();
				
		/*
		 * Page
		 */
		$pages = Versioned::get_by_stage('Page', 'Live')->filter(array('ShowInSearch' => 1));
		foreach ($pages as $p) {
			$Title = DB::getConn()->addslashes($p->Title);
			$Content = Purifier::PurifyTXT($p->Content);
			$Content = Purifier::RemoveEmbed($Content);
			$Content = DB::getConn()->addslashes($Content);
			DB::query("INSERT INTO SearchableDataObjects(ID,  ClassName, Title, Content) VALUES ("
							. "$p->ID, '$p->ClassName', '$Title', '$Content')");
		}
		
	}
}
