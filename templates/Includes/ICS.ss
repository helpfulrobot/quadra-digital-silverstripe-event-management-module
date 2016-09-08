<% with $Event %>
BEGIN:VCALENDAR
PRODID:-//Quadra Digital//SilverStripe Event Management Module//EN
VERSION:2.0
BEGIN:VEVENT
<% if $InstanceStartDateTime && $InstanceFinishDateTime %>
DTSTART:{$getUTCDateTime($InstanceStartDateTime)}
DTEND:{$getUTCDateTime($InstanceFinishDateTime)}
<% else %>
DTSTART:{$getUTCDateTime($StartDateTime)}
DTEND:{$getUTCDateTime($FinishDateTime)}
<% end_if %>
DTSTAMP:{$getUTCDateTime($Up.Now)}
<% with $getOrganiserData() %>ORGANIZER;CN={$Name}:mailto:{$Email}<% end_with %>
UID:Event{$ID}@{$getOrganiserData().Domain}
CREATED:{$getUTCDateTime($Created)}
LAST-MODIFIED:{$getUTCDateTime($LastEdited)}
SUMMARY:{$getSafeTitle(1,0)}
DESCRIPTION:{$getSafeDescription()}
X-ALT-DESC;FMTTYPE=text/html:{$getSafeDescription(0)}
<% if $Sequence > 0 %>SEQUENCE:{$Sequence}<% end_if %>
<% if $IsRecurring() && $Up.IncludeRecurrences %>RRULE:FREQ={$RecurFrequency};INTERVAL=1;UNTIL={$getUTCDateTime($RecurUntil)};<% end_if %>
END:VEVENT
END:VCALENDAR
<% end_with %>