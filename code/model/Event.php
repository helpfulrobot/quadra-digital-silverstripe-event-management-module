<?php

class Event extends DataObject {

    private static $db = array(
        'Title'             => 'Varchar(255)',
        'Description'       => 'HTMLText',
        'FinishDateTime'    => 'SS_DateTime',
        'StartDateTime'     => 'SS_DateTime',
        'Sequence'          => 'Int',
        'RecurFrequency'    => 'Varchar(255)',
        'RecurUntil'        => 'SS_DateTime'
    );

    private static $has_one = array(
        'Image'     => 'Image'
    );

    private static $defaults = array(
        'Sequence'  => 0
    );

    private static $summary_fields = array(
        'Title'             => 'Title',
        'StartDateTime'     => 'Starts',
        'FinishDateTime'    => 'Finishes'
    );

    private static $searchable_fields = array(
        'Title',
        'StartDateTime',
        'FinishDateTime'
    );

    /**
     * Key properties of an event which are used to determine whether the .ics
     * sequence number should be incremented when saving the Event
     *
     * @var array
     */
    public static $key_properties = array(
        'FinishDateTime',
        'StartDateTime',
        'RecurFrequency',
        'RecurUntil'
    );

    /**
     * Set of data values and humanly readable values for the different event
     * recurrence frequencies. Note that the keys (data values) must match the
     * allowed values for the .ics formats RRULE:FREQ=[XYZ]; property. See -
     * https://www.ietf.org/rfc/rfc2445.txt [Page 40]
     */
    public static $recurrence_frequencies = array(
        "DAILY"     => "Daily",
        "WEEKLY"    => "Weekly",
        "MONTHLY"   => "Monthly",
        "YEARLY"    => "Annually"
    );

    public function getCMSFields() {

        $fields = parent::getCMSFields();

        $fields->fieldByName('Root.Main.Sequence')
            ->setTitle("iCal Sequence No.")
            ->setDescription("
                Note: This will increment automatically if " . implode(', ', static::$key_properties) . " are updated.<br />
                If you want to force a new revision due to a change to another field you can do so here.
            ");

        $uploadField = new UploadField('Image','Image');
        $uploadField->setConfig('allowedMaxFileNumber', 1);
        $uploadField->setFolderName('Uploads/Events/');
        $uploadField->getValidator()->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));

        $startDateTime = new DateTimeField('StartDateTime', 'Event Start Date & Time');
        $startDateTime->getDateField()->setConfig('showcalendar', true);
        $startDateTime->getDateField()->setConfig('datevalueformat', 'YYYY-MM-dd');
        $startDateTime->getDateField()->setConfig('dateformat', 'dd-MM-YYYY');

        $finishDateTime = new DateTimeField('FinishDateTime', 'Event Finish Date & Time');
        $finishDateTime->getDateField()->setConfig('showcalendar', true);
        $finishDateTime->getDateField()->setConfig('dateformat', 'dd-MM-YYYY');
        $finishDateTime->getDateField()->setConfig('datevalueformat', 'YYYY-MM-dd');

        $fields->addFieldToTab('Root.Main', $startDateTime);
        $fields->addFieldToTab('Root.Main', $finishDateTime);
        $fields->addFieldToTab('Root.Main', $uploadField);

        // Replace Product::$Category DropdownField with HiddenField
        $fields->replaceField("Category", HiddenField::create("Category")->setValue("Event"));

        // Remove the original fields if you're adding custom ones
        $fields->removeByName('RecurFrequency');
        $fields->removeByName('RecurUntil');

        $recurUntil = DateTimeField::create("RecurUntil")
                        ->setTitle("Recur Until (Inclusive)")
                        ->setDescription("<a href=\"#\" class=\"js-toggle-field\" data-field-class=\"js-recur-helper\">Help me work it out</a>");
        $recurUntil->getDateField()->setConfig('showcalendar', true);

        $fields->findOrMakeTab('Root.Main')->Fields()->push(
                ToggleCompositeField::create(
                    "RecurrenceComposite",
                    "Recurrence Settings",
                    FieldList::create(
                        DropdownField::create("RecurFrequency")
                            ->setTitle("Recurrence Frequency")
                            ->setSource(static::$recurrence_frequencies)
                            ->setEmptyString('- No Recurrence -'),
                        $recurUntil,
                        FieldGroup::create(
                            // Never stored, just used to update the 'RecurUntil' date field via JS in /mysite/code/Admin/RecurringEvents.js
                            NumericField::create("_RecurIterations")
                            ->setTitle("")
                            )
                            ->addExtraClass("hidden js-recur-helper")
                            ->setTitle("No. of Recurrences")
                    )
                )->addextraClass("recurrence-settings")
            );

        return $fields;

    }

    public function getCMSValidator() {

        return new RequiredFields(
            array(
                'StartDateTime',
                'Title',
            )
        );

    }

    public function IsRecurring() {

        return (!empty($this->RecurFrequency));

    }

    public function IsPastEvent() {

        $now = time();
        $end = strtotime($this->FinishDateTime);

        return ($now >= $end);

    }

    /**
     * Get UTC DateTime
     *
     * Converts a date time to GMT/UTC. Primarily used for specifying
     * dates in UTC for calendar applications
     * 
     * @param  String $date
     * @param  String $format
     * @return String The date in GMT/UTC timezone formatted as per the $format paramter              
     */
    public function getUTCDateTime($date, $format = "Ymd\THis\Z") {
        
        return gmdate($format, strtotime($date));

    }

    /**
     * Get Organiser Data
     *
     * Determines the most suitable values to include in the iCalendar's 
     * 'ORGANIZER' and 'UID' properties. Sets safe defaults and overwrites 
     * with site specific data if possible.
     * 
     * @return ArrayData
     */
    public function getOrganiserData() {

        $domain = parse_url(Director::absoluteBaseURL(), PHP_URL_HOST);

        $data = array(
            "Name"      => "SilverStripe Event Management Module",
            "Email"     => "postmaster@" . $domain,
            "Domain"    => $domain
        );

        $sitename = SiteConfig::current_site_config()->Title;
        if(!empty($sitename)) {
            $data['Name'] = trim($sitename);
        }

        $adminEmail = Config::inst()->get('Email', 'admin_email');
        if(!empty($adminEmail)) {
            $data['Email'] = trim($adminEmail);
        }

        return new ArrayData($data);

    }

    /**
     * Get Safe Title
     *
     * Strips out any 'special' characters from the title so it can be used
     * for things like filenames or .ics descriptions
     *
     * @return String The title string stipped of any special characters
     */
    public function getSafeTitle($replacePeriods = true, $replaceSpaces = true) {

        $patterns = array(
            "A-Z",
            "0-9",
            "\-",
            "_"
        );

        if(empty($replacePeriods)) {
            // If not replacing periods, include them in the allowed characters pattern
            $patterns[] = "\.";
        }

        if(empty($replaceSpaces)) {
            // If not replacing spaces, include them in the allowed characters pattern
            $patterns[] = "\s";
        }

        $pattern = "/[^" . implode('', $patterns) . "]/i";

        $str = preg_replace($pattern, "_", $this->getTitle());

        return $str;

    }

    public function getSafeDescription($noHTML = true) {

        $content = new HTMLText();
        $content->setValue($this->Description);

        if(!empty($noHTML)) {
            $content = $content->NoHTML();
        }

        /*
         * String alterations in the format:
         *
         * array(
         *     'pattern' => 'replacement'
         * )
         */
        $alterations = array(
            "/:/" => "",
            "/\n/" => "\\n"
        );

        $content = preg_replace(array_keys($alterations), array_values($alterations), $content);

        return $content;

    }

    /**
     * Sets the StartDateTime and FinishDateTime properties of a given instance
     * of a recurring event
     *
     * @param String $timestamp - A timestamp representing the a day (no time element) which is
     * within the 'day span' of one of this events recurrences
     */
    public function setInstanceDateTimes($timestamp) {

        if(!$this->IsRecurring()) {
            error_log("Event::setInstanceDateTimes() has been called on a non-recurring event (ID #" . $this->ID . ")");
            return false;
        }

        $day           = date('Y-m-d', $timestamp);
        $dayOfWeek      = date('w', $timestamp);
        $dayOfMonth     = date('j', $timestamp);
        $dayOfYear      = date('z', $timestamp);

        $eventStartDay = date('Y-m-d', strtotime($this->StartDateTime));
        $eventFinishDay = date('Y-m-d', strtotime($this->FinishDateTime));
        $eventCurrentDay = $eventStartDay;

        // Build array of days which this event spans
        $eventDays = array();
        do {

            // Push event day into array
            $eventDays[$eventCurrentDay] = $this;

            // Increment event day by one day
            $eventCurrentDay = date('Y-m-d', strtotime($eventCurrentDay . ' +1 Day'));

        } while(strtotime($eventCurrentDay) <= strtotime($eventFinishDay));

        // For each day this event spans, see if the current day should include an ococurance of the event
        $startDayOffset = 0;
        foreach($eventDays as $eventCurrentDay => $event) {

            $eventDayOfWeek     = date('w', strtotime($eventCurrentDay));
            $eventDayOfMonth    = date('j', strtotime($eventCurrentDay));
            $eventDayOfYear     = date('z', strtotime($eventCurrentDay));

            if(
                ($event->RecurFrequency == "DAILY") ||
                ($event->RecurFrequency == "WEEKLY" && $eventDayOfWeek == $dayOfWeek) ||
                ($event->RecurFrequency == "MONTHLY" && $eventDayOfMonth == $dayOfMonth) ||
                ($event->RecurFrequency == "YEARLY" && $eventDayOfYear == $dayOfYear)
            ) {

                $occurrenceStartDate = date('Y-m-d', strtotime($day . " -" . $startDayOffset . " Days"));

                $startTime = date('H:i:s', strtotime($this->StartDateTime));

                $durationInSecs = strtotime($this->FinishDateTime) - strtotime($this->StartDateTime);

                $occurrenceStartDateTime = $occurrenceStartDate . " " . $startTime;
                $occurrenceFinishDateTime = date('Y-m-d H:i:s', strtotime($occurrenceStartDateTime) + $durationInSecs);

                $newStart = new SS_Datetime();
                $newStart->setValue($occurrenceStartDateTime);

                $newFinish = new SS_Datetime();
                $newFinish->setValue($occurrenceFinishDateTime);

                $this->InstanceStartDateTime = $newStart;
                $this->InstanceFinishDateTime = $newFinish;

                return $this;

            }

            // Increment the event start day offset
            $startDayOffset++;

        }

        return false;

    }

    /**
     * Used for recurring events which have already had their instance start/finish
     * date/times set. Calculates the timestamp of the current instances start day
     * (i.e. no time element) for use with the 'download calendar invite' logic
     *
     * @return Int
     */
    public function getInstanceTimestamp() {

        if(!isset($this->InstanceStartDateTime) || empty($this->InstanceStartDateTime)) {
            error_log("Event::getInstanceTimestamp() has been called on an event (ID #" . $this->ID . ") which has no 'InstanceStartDateTime' property set. Ensure Event::setInstanceDateTimes() has been called first!");
            return 0;
        }

        return strtotime(date('Y-m-d', strtotime($this->InstanceStartDateTime->getValue())));

    }

    public function setCalendarProperties($date, $recur = false) {

        /*
         * Only recurring events should ever call this method with a second date
         * param of $startDate
         */
        if(!empty($recur)) {
            $startField = "InstanceStartDateTime";
            $finishField = "InstanceFinishDateTime";
        } else {
            $startField = "StartDateTime";
            $finishField = "FinishDateTime";
        }

        if(date('Y-m-d', strtotime($this->getField($startField))) != date('Y-m-d', strtotime($this->getField($finishField)))) {
            // If this is a multi-day event
            $this->Span = 'ongoing';
        }

        if(date('Y-m-d', strtotime($this->getField($startField))) == $date) {
            // If the specified calendar date is the events start date
            $this->Position = 'first';
        } elseif (date('Y-m-d', strtotime($this->getField($finishField))) == $date) {
            // If the specified calendar date is the events finsh date
            $this->Position = 'last';
        }

        return $this;

    }

    /**
     * Deal with any extra logic that needs to be executed when an event's sequence is updated.
     * 
     * @param      Event  $event  The changed event so we can pull the necessary info from it.
     */
    public function UpdatedSequenceNotification($event = null) {

        /**
         * Add your own logic here
         */
        return;

    }

    public function onBeforeWrite() {

        /**
         * Set a default end date/time of the end of the day if no end time was set
         */
        if(!isset($this->FinishDateTime) || strtotime($this->FinishDateTime) < 1000) {
            $this->FinishDateTime = date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($this->StartDateTime)) . ' 23:59:59'));
        }

        /**
         * Increment the .ics calendar invite sequence number if any of the
         * key properties of this Event have been updated (after initital creation)
         */
        if(!empty($this->ID)) {
            $cf = $this->getChangedFields(true, 2);
            foreach(static::$key_properties as $property) {
                if(isset($cf[$property])) {
                    $this->Sequence = $this->Sequence + 1;
                    $this->UpdatedSequenceNotification($this);
                    break;
                }
            }
        }


        parent::onBeforeWrite();

    }

}

class Event_Controller extends Page_Controller {

    private static $allowed_actions = array(
        'iCalDownload'      => true,
        'ViewAsPage'        => true
    );

    private static $url_handlers = array(
        'calendar-invite/$EventID/$InstanceTimestamp/$IncludeRecurrences'   => 'iCalDownload',
        'event/$EventID/$InstanceTimestamp'                                 => 'ViewAsPage'
    );

    public function getTitle() {

        return "Event Details";

    }

    public function init() {

        parent::init();

        /**
         * Most methods on event shouldn't be indexed. If you need to over write for
         * a particular method, use removeHeader(), see {@link static::ViewAsPage}
         */
        $response = $this->getResponse();
        $response->addHeader("X-Robots-Tag", "noindex");

    }

    public static function iCalFilename($event, $incRecurrences = false) {

        $filename =  $event->getSafeTitle();
        $filename .= (!empty($event->Sequence)) ? "_Revision_" . $event->Sequence : "";
        $filename .= (!empty($incRecurrences)) ? "_Inc_Recurrences" : "";
        $filename .= ".ics";

        return $filename;

    }

    public static function iCalGenerate($event, $incRecurrences = false) {

        // Ensure that the correct theme is enabled and  used (to prevent issues
        // with calling this method statically as at times no SSViewer theme is set)
        Config::inst()->update('SSViewer', 'theme', 'default');
        Config::inst()->update('SSViewer', 'theme_enabled', true);

        $content = ViewableData::create()->customise(
            array(
                "Event"                     => $event,
                "Now"                       => SS_Datetime::now(),
                "IncludeRecurrences"        => $incRecurrences
            )
        )->renderWith("ICS");

        $content = preg_replace("/\n{2,}/", "\n", $content);
        $content = preg_replace("/\n/", "\r\n", $content);
        $content = trim($content);

        // Ensure ICS validation against length of lines
        $lines = explode("\r\n", $content);
        $content = "";
        foreach ($lines as $line) {
            // Each content line must begin with a space or tab, hence \t after the usual \r\n
            $content .= wordwrap($line, 70, "\r\n\t") . "\r\n";
        }

        return $content;

    }

    public function iCalDownload() {

        $eid = (int)$this->getRequest()->param("EventID");
        $timestamp = (string)$this->getRequest()->param("InstanceTimestamp");
        $incRecurrences = (string)($this->getRequest()->param("IncludeRecurrences") === "recur") ? true : false;

        $response = $this->getResponse();

        $event = Event::get()->byId($eid);
        if(!is_object($event) || !$event->exists()) {

            $content = $this->customise(
                array(
                    'Title'     => 'Error' ,
                    'Content'   => "Oops, we could not find any event with the ID #" . $eid
                )
            )->renderWith(
                array('EventPage', 'Page')
            );

            $response->setStatusCode(404);
            $response->setBody($content);

            return $response;

        }

        if(!empty($timestamp)) {
            $event->setInstanceDateTimes($timestamp);
        }

        $content = $this->iCalGenerate($event, $incRecurrences);
        $filename = $this->iCalFilename($event, $incRecurrences);

        $response->addHeader("Content-Type", "text/calendar");
        $response->addHeader("Content-Disposition", "attachment; filename=\"" . $filename . "\";");
        $response->addHeader("Content-Length", strlen($content));
        $response->setBody($content);

        return $response;

    }

    public function ViewAsPage() {

        // Dynamic event pages should still be indexed
        $this->getResponse()->removeHeader("X-Robots-Tag");

        // Include CSS
        Requirements::css('event-management/dist/layout.min.css');

        $timestamp = (string)$this->getRequest()->param("InstanceTimestamp");

        $eid = (int)$this->getRequest()->param("EventID");
        $event = DataObject::get('Event')->byId($eid);
        if(!is_object($event) || !$event->exists()) {
            $this->getResponse()->setStatusCode(404);
            return $this->customise(
                array(
                    "getTitle" => "Event ID #" . $eid . " Not Found!"
                )
            )->renderWith(array('EventPage', 'Page'));
        }

        if(!empty($timestamp)) {
            $event->setInstanceDateTimes($timestamp);
        }

        return $this->customise(
            array(
                "Event" => $event
            )
        )->renderWith(array('EventPage', 'Page'));

    }

}