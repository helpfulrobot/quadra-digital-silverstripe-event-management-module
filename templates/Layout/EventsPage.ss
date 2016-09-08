<div class="container" id="EventContent">
	<div class="row">
		<div class="col-xs-12">
			<h3 class="center">$getTitle()</h3>
		</div>
	</div>
	<% if not $calendarViewType %>
		<div class="row">
			<div class="calendar-filter">
				<a href="{$Link}/month/$PreviousMonthTimestamp" rel="nofollow" title="View Previous Months Calendar" class="prev medium">
					<div class="prev month">
						<h3 class="hidden-xs">$PreviousMonth('medium')</h3>
						<h3 class="visible-xs">$PreviousMonth('default')</h3>
					</div>
				</a>
				<div class="current month">
					<h3 class="hidden-xs">$CurrentMonth('large')</h3>
					<h3 class="visible-xs">$CurrentMonth('default')</h3>
				</div>
				<a href="{$Link}/month/$NextMonthTimestamp" rel="nofollow" title="View Next Months Calendar" class="next medium">
					<div class="next month">
						<h3 class="hidden-xs">$NextMonth('medium')</h3>
						<h3 class="visible-xs">$NextMonth('default')</h3>
					</div>
				</a>
			</div>
			<div class="calendar-holder cal-view clearfix">
				<a class="calendar-view switch-view">{$SVG('calendar-list')} Switch to <span class="visible-xs-inline">grouped event </span>list view</a>
				<section class="headers">
					<div class="day">Monday</div>
					<div class="day">Tuesday</div>
					<div class="day">Wednesday</div>
					<div class="day">Thursday</div>
					<div class="day">Friday</div>
					<div class="day">Saturday</div>
					<div class="day">Sunday</div>
				</section>
				<section class="calendar">
					<% if Dates %>
						<% loop Dates %>
							<div class="day<% if OutOfRange %> off-month<% else %><% if Events %> has-event<% else %> no-event<% end_if %><% end_if %>" date="$FullDate">
								<% if $HighVolume %><span class="view-more"><a class="calendar-view">&plus; <% if $additionalEvents == 0 %>1<% else %>$additionalEvents<% end_if %> more</a></span><% end_if %>
								<span class="date">
									<% if $Today %>
										<strong><a>
									<% end_if %>
									{$DayOfMonth}
									<% if $Today %>
										</a></strong>
									<% end_if %>
								</span>
								<% if Events %>
									<% loop Events %>

										<span
											class="event pos<% if $PositionCount %>{$PositionCount}<% else %>{$Pos}<% end_if %> {$Span} {$Position} {$Display}"
										>

											<span class="<% if $Position != "first" %>visible-xs <% end_if %>text">$getTitle(1)</span>

											<div class="event-information">
												<ul>
													<li>
														<span class="title">
															$Title
															<% if $IsRecurring() %>
																<br /><em>(Recurring event)</em>
															<% end_if %>
														</span>
													</li>
													<li>
														<span class="time-place">
															$StartDateTime.Format(H:ia) - $FinishDateTime.Format(H:ia)
														</span>
													</li>
													<% if $Description %>
														<li>$Description.FirstSentence</li>
													<% end_if %>
												</ul>
												<a href="{$Link}" class="btn pull-left">Read More</a>
												<% if $IsRecurring() %>
													<div class="dropdown pull-right">
														<button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
															Save event
															<span class="caret"></span>
														</button>
														<ul class="dropdown-menu">
															<li><a href="/download/calendar-invite/{$ID}/{$InstanceTimestamp}">This event occurrence only</a></li>
															<li><a href="/download/calendar-invite/{$ID}/{$InstanceTimestamp}/recur">All future event occurrences</a></li>
														</ul>
													</div>
												<% else %>
													<a href="/download/calendar-invite/{$ID}" class="btn pull-right">Save event</a>
												<% end_if %>
											</div>

										</span>

									<% end_loop %>
								<% end_if %>
							</div>
						<% end_loop %>
					<% else %>
						<p>Error: Can't Calculate Dates to Display Using the Given Month!</p>
					<% end_if %>
				</section>
			</div>
		</div>
	<% else %>
		<% include CalendarAgenda %>
	<% end_if %>
</div>