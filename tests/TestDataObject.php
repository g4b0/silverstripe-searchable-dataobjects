<?php
/**
 * Test DataObject
 */
class TestDataObject extends DataObject implements TestOnly, Searchable
{
    private static $db = array(
        'Title'    => 'Varchar',
        'Subtitle' => 'Varchar',
        'Content'  => 'HTMLText',
    );

    /**
     * Link to this DO
     * @return string
     */
    public function Link()
    {
        return $this->Page()->Link() . 'read/' . $this->ID;
    }

    /**
     * Filter array
     * eg. array('Disabled' => 0);
     * @return array
     */
    public static function getSearchFilter()
    {
        return array();
    }

    /**
     * FilterAny array (optional)
     * eg. array('Disabled' => 0, 'Override' => 1);
     * @return array
     */
    public static function getSearchFilterAny()
    {
        return array();
    }

    /**
     * FilterByCallback function (optional)
     * eg. function($object){
     *  return ($object->StartDate > date('Y-m-d') || $object->isStillRecurring());
     * };
     * @return array
     */
    // public static function getSearchFilterByCallback()
    // {
    //     return function($object){ return true; };
    // }

    /**
     * Fields that compose the Title
     * eg. array('Title', 'Subtitle');
     * @return array
     */
    public function getTitleFields()
    {
        return array('Title');
    }

    /**
     * Fields that compose the Content
     * eg. array('Teaser', 'Content');
     * @return array
     */
    public function getContentFields()
    {
        return array('Subtitle', 'Content');
    }
}
