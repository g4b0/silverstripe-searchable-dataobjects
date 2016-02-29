<?php

/**
 * Searchable - interface to implement in order to be a searchable DO
 *
 * @author Gabriele Brosulo <gabriele.brosulo@zirak.it>
 * @creation-date 09-May-2014
 */
interface Searchable
{
    
    /**
     * Link to this DO
     * @return string
     */
    public function Link();
    
    /**
     * Filter array
     * eg. array('Disabled' => 0);
     * @return string
     */
    public static function getSearchFilter();
    
    /**
     * Fields that compose the Title
     * eg. array('Title', 'Subtitle');
     * @return array
     */
    public function getTitleFields();
    
    /**
     * Fields that compose the Content
     * eg. array('Teaser', 'Content');
     * @return array
     */
    public function getContentFields();
}
