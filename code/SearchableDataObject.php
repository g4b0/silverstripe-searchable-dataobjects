<?php

/**
 * SearchableDataObject - interface to implement in order to be a searchable DO
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 09-May-2014
 */
interface SearchableDataObject {
	
	/**
	 * Link to this DO
	 * @return string
	 */
	public function Link();
	
}
