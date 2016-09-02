var CalendarViewClickOnLoad = false;

/*
 * Get the calendar view cookie and alter it. If the cookie exists then the calendar view is the 'agenda view', else it is the standard calendar view
 */
$(document).on(
    'click',
    '.calendar-view',
    function(event) {

        var address = location.href;

        if(!CalendarViewClickOnLoad) {
            CalendarViewClickOnLoad = true;

            var cookies = document.cookie;
            var date = $(this).closest('.day').attr('date');

            if (cookies.indexOf('CalendarView=Agenda') > -1) {
                document.cookie="CalendarView=Agenda; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;";
                location.href = address.split('#')[0];
            } else {
                document.cookie="CalendarView=Agenda; path=/; expires=Thu, 01 Jan 2030 00:00:01 GMT;";
                if (undefined !== date) {
                    location.href = address + '/#' + date;
                } else {
                    location.href = address;
                }
            }

        } else {
            event.preventDefault();
        }
    }
);