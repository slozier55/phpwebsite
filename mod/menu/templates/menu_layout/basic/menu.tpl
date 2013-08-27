<div class="menu" id="{MENU_ID}">
	<div>
	<!-- BEGIN menu_admin -->
		<div class="btn-group">
			<button class="btn dropdown-toggle btn-sm pull-right" data-toggle="dropdown"> <i class="icon-list">
			  </i> Menu Options <span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<li>{PIN_LINK}</li>
				<li>{ADD_LINK}</li>
				<li>{ADD_SITE_LINK}</li>
				<li class="divider"></li>
				<li>{CLIP}</li>
			</ul>
		</div>
    <!-- END menu_admin -->
		<h5 class="menu-title">{TITLE}</h5>
        <div class="clearfix"></div>
	</div>
	<ul id="sort-{MENU_ID}" class="nav nav-pills nav-stacked">
	  {LINKS}
	</ul>
	<div>
		<!-- BEGIN pin -->
		{PIN_PAGE}<br />
		<!-- END pin -->
		{ADMIN_LINK}
	</div>
</div>