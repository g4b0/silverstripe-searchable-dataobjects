<?php

class SearchPage extends Page
{

    private static $db = array(
    );

    private static $has_one = array(
    );

    private static $defaults = array(
        'ShowInMenu' => false
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        return $fields;
    }
}

class SearchPage_Controller extends Page_Controller
{
}
