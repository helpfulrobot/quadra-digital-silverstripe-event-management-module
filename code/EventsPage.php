<?php

class EventsPage extends Page {

    private static $icon = '/event-management/code/admin/images/menu-icons/16x16/events.png';

    private static $description = "Displays all events in either a calendar or list view.";

    private static $allowed_children = "none";

    public function getCMSFields() {
        $fields = parent::getCMSFields();
        $fields->removeByName('Content');
        return $fields;
    }

}

class EventsPage_Controller extends Page_Controller {

	public $CurrentMonth;
    public $Dates;

    public static $allowed_actions = array(
        'Month' => true
    );

    private static $url_handlers = array(
        'month/$Month' => 'Month'
    );

    public function init() {

        parent::init();

        Requirements::css("event-management/dist/layout.min.css");
        Requirements::javascript("event-management/dist/dependencies.min.js");
        Requirements::javascript("event-management/dist/script.min.js");

    }

    public function getTitle() {
        return "Events";
    }

    public function Link($action = null) {
        return "events";
    }

    // Run the Month() Function by Default
    public function index() {
        return $this->Month();
    }

    // Stores an ArrayList of a Months (Based on a Given TimeStamp) Worth of Dates. Includes Appropriate Starting & Ending Offset for Leading/Trailing Weekdays
    public function Month() {

        // Turn Debug Error Logging On/Off
        $debug = false;

        // Get Any Passed Timestamp Parameters
        $m = $this->request->param("Month");

        if (empty($m)) {
            $m = strtotime(date('Y-m', time()));
        }

        // Calculate Start/End of Month Dates (Using Todays Month if No Timestamp is Provided)
        $monthStartDate = (!empty($m) && is_numeric($m)) ? date('Y-m-d H:i:s', strtotime(date('Y-m', $m))) : date('Y-m-d H:i:s', strtotime(date('Y-m', time())));
        $monthEndDate = date("Y-m-d H:i:s", strtotime($monthStartDate . " +1 month") - 1);

        // Define Curent Calendar Month For Previous/Next Month Functions
        $this->CurrentMonth = $monthStartDate;

        // Calculate Leading Weekday Offset (Calendar First Day is Always Monday)
        $monthStartDay = strtoupper(date('D', strtotime($monthStartDate)));
        switch($monthStartDay) {
            case "MON"  : $startOffset = 0; break;
            case "TUE"  : $startOffset = 1; break;
            case "WED"  : $startOffset = 2; break;
            case "THU"  : $startOffset = 3; break;
            case "FRI"  : $startOffset = 4; break;
            case "SAT"  : $startOffset = 5; break;
            case "SUN"  : $startOffset = 6; break;
            default     : $startOffset = 0;
        }

        // Calculate Trailing Weekday Offset (Calendar Last Day is Always Sunday)
        $monthEndDay = strtoupper(date('D', strtotime($monthEndDate)));
        switch($monthEndDay) {
            case "SUN"  : $endOffset = 0; break;
            case "SAT"  : $endOffset = 1; break;
            case "FRI"  : $endOffset = 2; break;
            case "THU"  : $endOffset = 3; break;
            case "WED"  : $endOffset = 4; break;
            case "TUE"  : $endOffset = 5; break;
            case "MON"  : $endOffset = 6; break;
            default     : $endOffset = 0;
        }

        // Calculate Date Range For This Month (Includes Offsets)
        $calStartDate = date('Y-m-d H:i:s', (strtotime($monthStartDate) - (60*60*24*$startOffset)));
        $calEndDate = date('Y-m-d H:i:s', (strtotime($monthEndDate) + (60*60*24*$endOffset)));
        // Calculate How Many Dates Are in the Given Date Set (Normally 35 but could be 28 or 42)
        $numOfDays = round((strtotime($calEndDate) / (60*60*24)) - (strtotime($calStartDate) / (60*60*24)));

        // Debug Messages
        if($debug) {
            error_log("Month Start Date ".$monthStartDate);
            error_log("Month Start Day ".$monthStartDay);
            error_log("Cal Start Offset ".$startOffset);
            error_log("Cal Start Date ".$calStartDate);
            error_log("Month End Date ".$monthEndDate);
            error_log("Month End Day ".$monthEndDay);
            error_log("Cal End Offset ".$endOffset);
            error_log("Cal End Date ".$calEndDate);
            error_log("No of Days ".$numOfDays);
        }

        // Create Our Empty ArrayList for Each Date Array to be Pushed Into
        $dates = new ArrayList();
        $date = date('Y-m-d', strtotime($calStartDate));
        // Foreach Date in the Date Set
        for($i = 1; $i <= $numOfDays; $i++) {

            // Calculate the Starting and Ending Seconds of the Given Date
            $startOfDay = date('Y-m-d H:i:s', strtotime($date));
            $endOfDay = date('Y-m-d H:i:s', (strtotime($date) + ((60*60*24) - 1)));

            // Define ArrayList to hold all events which occour on this day
            $events = new ArrayList();

            // Get Any Regular Events Occouring On The Given Day
            $regularEvents = Event::get()
                ->where("\"RecurFrequency\" IS NULL AND (\"StartDateTime\" BETWEEN '".$startOfDay."' AND '".$endOfDay."' OR '".$startOfDay."' BETWEEN \"StartDateTime\" AND \"FinishDateTime\")")
                ->sort("\"FinishDateTime\" ASC");

            // Process and add all regular events
            foreach($regularEvents as $event) {
                $event->setCalendarProperties($date);
                $events->push($event);
            }

            // Build WHERe condition for suitable recurring events
            $where = "";
            $where .= "\"RecurFrequency\" IS NOT NULL AND "; // It's a reccouring event
            $where .= "\"StartDateTime\" <= '" . $endOfDay . "' AND "; // who's very first occourance happens on or before the end of the current day (i.e. if the recuring event has not even started yet iognore it)
            $where .= "\"RecurUntil\" >= '" . $startOfDay . "'"; // and is set to continue reccuring this day or further in the future (i.e. it has not had it's final occourance laready in the past)

            // Get Any Recurring Events Which COULD potentially Occour On The Given Day
            $recurringEvents = Event::get()->where($where)->sort("\"FinishDateTime\" ASC");

            // Filter Out Events Which Do Not Occour On The Given Day, Process and Add The Rest
            foreach($recurringEvents as $event) {

                /*
                 * If we are able to set some instance start/finish date/times
                 * based on the current calendar day then this event occours on this day
                 */
                if($event->setInstanceDateTimes(strtotime($date))) {
                    // Set events calendar properties (classes define styling in calendar)
                    $event->setCalendarProperties($date, true);
                    // Add event to our ArrayList of events occouring on this calendar day
                    $events->push($event);
                }

            }

            // Populate Our Date Array with All Data we Need in the Template
            $dates->add(new ArrayData(array(
                "FullDate" => $date,
                "NiceDate" => date('l jS F', strtotime($date)),
                "MonthNo"   => date('n', strtotime($date)),
                "DayOfMonth" => date('j', strtotime($date)),
                "OutOfRange" => (date('m', strtotime($date))  == date('m', strtotime($monthStartDate))) ? 0 : 1,
                "HasEvent" => (!empty($events)) ? 1 : 0,
                "Events" => (!empty($events)) ? $events : 0
            )));


            // Move the Counter Forward to the Next Day
            $date = date("Y-m-d", strtotime($date . " +1 day"));
        }

        // Store the Date Set
        $this->Dates = $dates;

        // Create the events position array list which will store ALL events and their position.
        $eventPositions = array();

        foreach($this->Dates as $date) {
            // Create the dayPos array which will store all event positions for the current day in the loop.
        	$dayPos = array('1' => null, '2' => null, '3' => null);
        	$count = 0;

            // If the day in the loop is equal to todays date, set today to true.
            if ($date->FullDate == date('Y-m-d')) {
                $date->Today = true;
            }

            // Check if there are more events on the current day than can fit in the calendar
            if ($date->Events->count() > 3) {
                $date->HighVolume = true;
            }

            $moreEvents = 0;

        	foreach($date->Events as &$event) {
        		$count++;

                // Specify the timestamp of this particular event (for the benefit of recurring events)
                $event->InstanceTimestamp = strtotime($date->FullDate);

                // Set the event's link attribute to the link of the corresponding events page.
                $event->Link = 'events/event/' . $event->ID;
                if(!empty($event->RecurFrequency)) {
                    $event->Link .= "/" . $event->InstanceTimestamp;
                }

                // If the events position array with the key of the events ID is not empty, then ensure the event stays in this position
                // for all of the following days.
        		if(!empty($eventPositions[$event->ID]['Position'])) {

                    // Set the events position to what it is set to on all other days previous to this one.
        			$event->Pos = $eventPositions[$event->ID]['Position'];

                    /*
                     * Ensure if any other event already holds this position on this day, it is not
                     * lost by forcing the event with the earlier start date into it's position.
                     * Instead it should be 'nudged' down the position list until it find an empty
                     * position (i.e. 2, 3, 5, 210 if needs be!!)
                     */
                    if(isset($dayPos[$event->Pos])) {
                        // Store the ID of the event we are 'nudging' down
                        $demotedEventID = $dayPos[$event->Pos];
                        // Store the position we are moving it from in $pos
                        $pos = $event->Pos;
                        while(isset($dayPos[$pos])) {
                            // Increment $pos until you find an empty space in the positon array
                            $pos++;
                        }
                        // Put the demoted/nudged down event into it's new place in the day's position array
                        $dayPos[$pos] = $demotedEventID;
                        /*
                         * Lookup the demoted event object in $date->Events by it's ID and update
                         * it's 'Pos' property to reflect it's new demoted position
                         */
                        $date->Events->find('ID', $demotedEventID)->Pos = $pos;
                        /*
                         * Update the demoted events 'global' position identifier used for positioning
                         * this event on future days (if it is a multi-day event)
                         * */
                        $eventPositions[$demotedEventID]['Position'] = $pos;
                    }

                    // Populate the corresponding array entry (either 1, 2 or 3) to the events ID to mark it as occupied so other events don't fill it.
                    $dayPos[$event->Pos] = $event->ID;

        		} else {

                    // Check if the dayPos array with the key of the count is empty
                    if(empty($dayPos[$count]) && $count) {

                        // If the event doesn't already have a set position, set it to the current count in the loop.
                        $dayPos[$count] = $event->ID;

                        // Now create the entry in eventPositions to notify the event to take this position on the following days it exists.
                        $eventPositions[$event->ID]['Position'] = $count;

                    } else {

                        // If the entry is not empty, we need to loop and find the next available entry.
                        foreach($dayPos as &$pos) {

                            // If the current position is not set, then we can use this position for the current event.
                            if(!isset($pos)) {

                                // Set the value of the current array entry as this events ID.
                                $pos = $event->ID;

                                // Get the key of the slow we just assiged the event ID to and set that as the position of the event.
                                $posKey = array_search($pos, $dayPos);
                                $event->Pos = $posKey;

                                // Set the event positions array with the event id as key to have a value of the position we are taking.
                                $eventPositions[$event->ID]['Position'] = $posKey;
                                break;

                            }

                        }

                    }

        		}

                // if the current date has more than 3 events then hide all events except the ones in positions 1 - 3
                if($date->HighVolume && $eventPositions[$event->ID]['Position'] > 3) {
                    $eventPositions[$event->ID]['Display'] = 'hidden';
                    $moreEvents++;
                }

                // If an event has a hidden section on another day, be sure to hide the rest of the sections so it doesn't look out of place.
                if(!empty($eventPositions[$event->ID]['Display'])) {
                    $event->Display = 'hidden';
                    $date->HighVolume = true;
                }
        	}

            $date->additionalEvents = $moreEvents;
        }

        // Run a second loop through dates and events so we can hide events on previous days that are hidden in the previous loop
        foreach ($this->Dates as $date) {
            foreach ($date->Events as &$event) {
                if (!empty($eventPositions[$event->ID]['Display'])) {
                    $event->Display = 'hidden';
                    $date->HighVolume = true;
                }
            }
        }

        return $this->renderWith(array("EventsPage", "Page"));

    }

    public function calendarViewType() {
        // Get the agenda cookie and check if it is set. If so then show the agenda view.
        $agenda = Cookie::get('CalendarView');
        if (empty($agenda) || !isset($agenda)) {
            $calendar = false;
        } else {
            $calendar = true;
        }

        return $calendar;
    }

    // Returns The Starting Second of the Previous Month in Timestamp Form
    public function PreviousMonthTimestamp() {
        return strtotime($this->CurrentMonth . "-1 Month");
    }

    // Returns The Starting Second of the Next Month in Timestamp Form
    public function NextMonthTimestamp() {
        return strtotime($this->CurrentMonth . "+1 Month");
    }

    // Formats the Previous Month/Year Combination Based on the Provided Format
    public function PreviousMonth($size = 'large') {
        switch($size) {
            case 'large'    : $format = 'F Y'; break;
            case 'medium'   : $format = 'M Y'; break;
            case 'small'    : $format = 'm/y'; break;
            default         : $format = 'M y';
        }
        return date($format, $this->PreviousMonthTimestamp());
    }

    // Formats the Current Month/Year Combination Based on the Provided Format
    public function CurrentMonth($size = 'large') {
        switch($size) {
            case 'large'    : $format = 'F Y'; break;
            case 'medium'   : $format = 'M Y'; break;
            case 'small'    : $format = 'm/y'; break;
            case 'number'   : $format = 'n'; break;
            default         : $format = 'M y';
        }
        return date($format, strtotime($this->CurrentMonth));
    }

    // Formats the Next Month/Year Combination Based on the Provided Format
    public function NextMonth($size = 'large') {
        switch($size) {
            case 'large'    : $format = 'F Y'; break;
            case 'medium'   : $format = 'M Y'; break;
            case 'small'    : $format = 'm/y'; break;
            default         : $format = 'M y';
        }
        return date($format, $this->NextMonthTimestamp());
    }

}