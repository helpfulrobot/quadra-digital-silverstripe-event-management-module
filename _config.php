<?php

// Add Custom CSS & JS the CMS UI
Config::inst()->update(
    'LeftAndMain',
    'extra_requirements_css',
    array(
        'event-management/code/admin/css/RecurringEvents.css' => array()
    )
);
Config::inst()->update(
    'LeftAndMain',
    'extra_requirements_javascript',
    array(
        'event-management/code/admin/js/moment.js' => array(),
        'event-management/code/admin/js/RecurringEvents.js' => array()
    )
);