<?php

/**
 * SearchableDataObject - extension that let the DO to auto update the search table 
 * after a write
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 12-May-2014
 */
class SearchableDataObject extends DataExtension {
	
	private function deleteDo(DataObject $do) {
		$id = $do->ID;
		$class = $do->class;
		DB::query("DELETE FROM SearchableDataObjects WHERE ID=$id AND ClassName='$class'");
	}
	
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		if (in_array('Searchable', class_implements($this->owner->class))) {
			if($this->owner->hasExtension('Versioned')) {
		            $filterID = array('ID' => $this->owner->ID);
		            $filter = $filterID + $this->owner->getSearchFilter();
		            $do = Versioned::get_by_stage($this->owner->class, 'Live')->filter($filter)->first();
		        } else {
		            $filterID = "`{$this->owner->class}`.`ID`={$this->owner->ID}";
		            $do = DataObject::get($this->owner->class, $filterID, false)->filter($this->owner->getSearchFilter())->first();
		        }
		        
			if ($do) {
				PopulateSearch::insert($do);
			} else {
				$this->deleteDo($this->owner);
			}
		} else if ($this->owner instanceof Page) {
			PopulateSearch::insertPage($this->owner);
		}
	}
	
	/**
	 * Remove the entry from the search table before deleting it
	 */
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		
		$this->deleteDo($this->owner);
	}
	
}
