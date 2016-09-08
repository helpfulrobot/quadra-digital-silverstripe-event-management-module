<?php

class EventsAdmin extends ModelAdmin {

    public static $managed_models = array(
        'Event'    => array('title' => 'Events'),
    );

    public static $url_segment = 'events';
    public static $menu_priority = 0.3;
    public static $menu_title = "Events";
    public static $menu_icon = '/event-management/code/admin/images/menu-icons/16x16/events-icon.png';

    public $showImportForm = false;

    public function getEditForm($id = null, $fields = null) {

        $form = parent::getEditForm($id, $fields);

        // Event specific settings
        if($this->modelClass == 'Event') {

            $gridFieldName = $this->sanitiseClassName($this->modelClass);
            $gridField = $form->Fields()->fieldByName($gridFieldName);

            $config = $gridField->getConfig();

            // Configure 'Add New' button text
            $config->getComponentByType("GridFieldAddNewButton")->setButtonName("Add Event");

        }

        return $form;

    }

}