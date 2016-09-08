<div class="container" id="EventContent">
	<div class="row">
		<div class="col-xs-12">
			<h3 class="center">$getTitle()</h3>
		</div>
	</div>
	<% if Event %>
		<% with Event %>

			<div class="row">
				<div class="col-xs-12">

					<% if $Image %>
						<div class="image-holder">
							<% with $Image %>
								<picture>
		                            <source media="(max-width: 320px)" srcset="{$CroppedImage(320, 160).URL}">
		                            <source media="(max-width: 480px)" srcset="{$CroppedImage(480, 240).URL}">
		                            <source media="(max-width: 767px)" srcset="{$CroppedImage(767, 370).URL}">
		                            <source media="(max-width: 991px)" srcset="{$CroppedImage(600, 300).URL}">
		                            <img src="{$CroppedImage(1040, 520).URL}" alt="{$Title}" />
		                        </picture>
		                    <% end_with %>
						</div>
					<% end_if %>

				</div>
			</div>

			<div class="row">
				<div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">

					<div class="row">
						<div class="col-xs-12">
							<h4 class="text-center">
								$Title
							</h4>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-2 col-xs-12">
							<p><strong>When:</strong></p>
						</div>
						<div class="col-sm-10 col-xs-12">
							<% if $IsRecurring() %>
								<p>$InstanceStartDateTime.Format(M d Y H:i) - $InstanceFinishDateTime.Format(M d Y H:i)</p>
								<p class="disclaimer">This event occurs on a <span style="text-transform: lowercase;">$RecurFrequency</span> basis between $StartDateTime.Format(M d Y) and $RecurUntil.Format(M d Y)</p>
							<% else %>
								<p>$StartDateTime.Format(M d Y H:i) - $FinishDateTime.Format(M d Y H:i)</p>
							<% end_if %>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-2 col-xs-12">
							<p><strong>What:</strong></p>
						</div>
						<div class="col-sm-10 col-xs-12">
							$Description
						</div>
					</div>

				</div>
			</div>

			<div class="row">
				<div class="col-xs-12">
					<a href="/events" class="btn pull-left">&larr; Back to all events</a>
					<% if $IsRecurring() %>
						<div class="dropdown pull-right">
							<button class="btn dropdown-toggle" type="button" data-toggle="dropdown">
								Save event
								<span class="caret"></span>
							</button>
							<ul class="dropdown-menu">
								<li><a href="/download/calendar-invite/{$ID}/{$getInstanceTimestamp()}">This event occurrence only</a></li>
								<li><a href="/download/calendar-invite/{$ID}/{$getInstanceTimestamp()}/recur">All future event occurrences</a></li>
							</ul>
						</div>
					<% else %>
						<a href="/download/calendar-invite/{$ID}" class="btn pull-right">Save event</a>
					<% end_if %>
				</div>
			</div>

		<% end_with %>
	<% end_if %>

</div>