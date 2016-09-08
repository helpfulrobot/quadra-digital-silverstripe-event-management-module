<div class="row">
		<div class="col-xs-12">
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
		</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<div class="calendar-holder agenda clearfix">
			<a class="calendar-view switch-view">{$SVG('calendar-view')} Switch to calendar view</a>
			<section class="calendar">
				<% if Dates %>
					<% loop Dates %>
						<% if Events %>
							<% if not $OutOfRange %>
								<div id="{$FullDate}" class="day<% if Events %> has-event<% else %> no-event<% end_if %>">
									<h4>$NiceDate</h4>
									<% loop Events %>
										<div class="single-event clearfix">
											<div class="row">
												<div class="col-sm-3">
													<a href="{$Link}"><p class="title">$Title</p></a>
													<p class="time-place">
														$StartDateTime.Format(H:ia) - $FinishDateTime.Format(H:ia)
													</p>
												</div>
												<div class="col-sm-6">
													$Description
												</div>
												<div class="col-sm-3">
													<a href="{$Top.Link}/event/{$ID}" class="btn transparent">Read More</a>&nbsp;
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
														<a href="/download/calendar-invite/{$ID}" class="btn margin-right pull-right">Save event</a>
													<% end_if %>
												</div>
											</div>
										</div>
									<% end_loop %>
								</div>
							<% end_if %>
						<% end_if %>
					<% end_loop %>
				<% else %>
					<p>Error: Can't Calculate Dates to Display Using the Given Month!</p>
				<% end_if %>
			</section>
		</div>
	</div>
</div>