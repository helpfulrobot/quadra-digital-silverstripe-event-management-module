(function($) {

    var startDateTimeout;
    var recurUntilTimeout;

    var calculateFinalOccurrence = function() {

        var startDateField = $('input[name="StartDateTime\\[date\\]"]');
        var startTimeField = $('input[name="StartDateTime\\[time\\]"]');
        var finishDateField = $('input[name="FinishDateTime\\[date\\]"]');
        var finishTimeField = $('input[name="FinishDateTime\\[time\\]"]');
        var recurUntilDateField = $('input[name="RecurUntil\\[date\\]"]');
        var recurUntilTimeField = $('input[name="RecurUntil\\[time\\]"]');
        var recurFrequencyField = $('select[name="RecurFrequency"]');
        var recurIterationsField = $('input[name="_RecurIterations"]');

        // Exit early if any of the fields we need can't be found
        if(
            startDateField.length == 0 ||
            startTimeField.length == 0 ||
            finishDateField.length == 0 ||
            finishTimeField.length == 0 ||
            recurUntilDateField.length == 0 ||
            recurUntilTimeField.length == 0 ||
            recurFrequencyField.length == 0 ||
            recurIterationsField.length == 0
        ) {
            alert("An error was encountered, please manually populate the 'Recur Until' field");
            console.log("1 or more input fields are missing");
            return;
        }

        var recurIterations = recurIterationsField.val();
        if(recurIterations.match(/[^0-9]/)) {
            recurUntilDateField.val("");
            recurUntilTimeField.val("");
            alert("Please enter digits only");
            return;
        }

        // Parse iterations as an interger
        recurIterations = parseInt(recurIterations);

        // Clear the date field if no iterations are specified
        if(recurIterations == 0 || isNaN(recurIterations)) {
            recurUntilDateField.val("");
            recurUntilTimeField.val("");
            console.log("Recur Iterations is empty");
            return;
        }

        // Exit early if the event is not marked as having a start date yet
        var startDate = startDateField.val();
        if(startDate == "") {
            alert("Please enter a start date");
            console.log("Start Date is empty");
            return;
        }

        // Exit early if the event is not marked as having a start date yet
        var startTime = startTimeField.val();
        if(startTime == "") {
            alert("Please enter a start time");
            console.log("Start Time is empty");
            return;
        }

        // Exit early if the event is not marked as having a start date yet
        var finishDate = finishDateField.val();
        if(finishDate == "") {
            alert("Please enter a finish date");
            console.log("Finish Date is empty");
            return;
        }

        // Exit early if the event is not marked as having a start date yet
        var finishTime = finishTimeField.val();
        if(finishTime == "") {
            alert("Please enter a finish time");
            console.log("Finish Time is empty");
            return;
        }

        // Exit early if the event is not marked as having a recurrance frequency
        var recurFrequency = recurFrequencyField.val();
        if(recurFrequency == "") {
            recurUntilDateField.val("");
            recurUntilTimeField.val("");
            console.log("Recur Frequency is empty");
            return;
        }

        // Calculate interval to use with moment.js add() method
        switch(recurFrequency) {
            case "DAILY"    : var increment = "days"; break;
            case "WEEKLY"   : var increment = "weeks"; break;
            case "MONTHLY"  : var increment = "months"; break;
            case "YEARLY"   : var increment = "years"; break;
            default         : var increment = "days";
        }

        // Calculate the final date based on the start date, recurrence
        // frequency and no. of recurrences and event duration (to ensure multi-day recuring events work)
        var firstOccurrenceStart = moment(startDate + ' ' + startTime, "DD-MM-YYYY HH:mm:ss");
        var firstOccurrenceFinish = moment(finishDate + ' ' + finishTime, "DD-MM-YYYY HH:mm:ss");
        var finalOccurrenceStart = firstOccurrenceStart.add(recurIterations, increment);

        if(firstOccurrenceFinish.isAfter(firstOccurrenceStart, "day")) {
            var durationInDays = firstOccurrenceFinish.subtract(firstOccurrenceStart, "days");
            var finalOccurrenceFinish = finalOccurrenceStart.add(durationInDays, "days");
        } else {
            var finalOccurrenceFinish = finalOccurrenceStart;
        }

        // Update the date and time fields to display the final start date/time
        recurUntilDateField.val(finalOccurrenceFinish.format("YYYY-MM-DD"));
        recurUntilTimeField.val(finalOccurrenceFinish.format("HH:mm:ss"));

    }

    var displayRecurrenceWarning = function() {

        var recurUntilDateField = $('input[name="RecurUntil\\[date\\]"]');
        var recurUntilTimeField = $('input[name="RecurUntil\\[date\\]"]');

        if(recurUntilDateField.length == 0 && recurUntilTimeField.length == 0) {
            return;
        }

        if(recurUntilDateField.val() == "" && recurUntilTimeField.val() == "") {
            return;
        }

        alert("It seems like you are updating the start date of a recurring event?\n\nDon't forget that this event has a fixed 'Recur Until' date & time, this will not be updated automatically when you change the start date or time so please ensure you update it if neccersary.");
        return;

    }

    $(document).on(
        "keyup",
        'input[name="_RecurIterations"]',
        function(e) {

            if(undefined !== recurUntilTimeout) {
                clearTimeout(recurUntilTimeout);
            }

            recurUntilTimeout = setTimeout(calculateFinalOccurrence, 1000);

        }
    );

    $(document).on(
        "change",
        'select[name="RecurFrequency"]',
        function(e) {

            if(undefined !== recurUntilTimeout) {
                clearTimeout(recurUntilTimeout);
            }

            recurUntilTimeout = setTimeout(calculateFinalOccurrence, 1);

        }
    );

    $(document).on(
        "change",
        'input[name="StartDateTime\\[date\\]"], input[name="StartDateTime\\[time\\]"]',
        function(e) {

            if(undefined !== startDateTimeout) {
                clearTimeout(startDateTimeout);
            }

            startDateTimeout = setTimeout(displayRecurrenceWarning, 1000);

        }
    );

    $(document).on(
        "click",
        "a.js-toggle-field",
        function(e) {

            e.preventDefault();

            var id = $(this).attr('data-field-class');

            var field = $(this).parents('form').find('.' + id);
            if(field.length > 0) {
                field.toggleClass('hidden');
            }

        }
    );

})(jQuery);