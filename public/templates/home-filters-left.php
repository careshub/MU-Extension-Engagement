<?php 

	/**
	 *
	 **/

?>
	
		<div id="filters-container-left" class="col-xs-12 clearfix filter-box">
			<div id="filter-selections" class="clearfix">

			<fieldset class="collapsible">
			<legend><h6><i class="fa fa-chevron-up icon"></i> MY COMMUNITY</h6></legend>
			<div>
			<select id="filters-container-regions" class="form-control">
			</select>
			<select id="list-geography" class="form-control">
			</select>

			<div id="filter-help">
				Looking for engagement programs and activities occurring in your community? 
				Select your community from the list or map to find out!
			</div>

			<ul id="filter-geography">
			</ul>
			</div>
			</fieldset>

			<fieldset class="collapsible">
			<legend><h6><i class="fa fa-chevron-up icon"></i> ENGAGEMENT IMPACT AREA</h6></legend>
			<div>
			<ul id="filter-theme">
			</ul>
			</div>
			</fieldset>

			<fieldset class="collapsible collapsed">
			<legend><h6><i class="fa fa-chevron-down icon"></i> ENGAGEMENT TYPE</h6></legend>
			<div>
			<ul id="filter-type">
			</ul>
			</div>
			</fieldset>

			<fieldset class="collapsible collapsed">
			<legend><h6><i class="fa fa-chevron-down icon"></i> AFFILIATION</h6></legend>
			<div>
			<ul id="filter-affiliation">
			</ul>
			</div>
			</fieldset>

			<fieldset class="collapsible" id="style-container">
			<legend><h6><i class="fa fa-chevron-up icon"></i> DISPLAY STYLE</h6></legend>
			<div class="display-style">
			<i class="fa fa-th fa-3x active" title="Grid View" id="grid-view"></i>
			<i class="fa fa-list fa-3x" title="List View" id="list-view"></i>
			</div>
			</fieldset>
			
				<div class='side-menu'>
				<h4>Go To:</h4>
				<?php if( !is_user_logged_in() ) { ?>
					<a href="<?php echo get_home_url(); ?>/wp-login.php?action=use-sso">Log In</a>
				<?php } else { 
					 wp_nav_menu( array(
						'theme_location' => 'top-logged-in',
						'menu_id'        => 'top-menu-logged-in',
					) );
				} ?>
				</div>
			</div>
			
			
		</div>
			