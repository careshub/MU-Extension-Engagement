<?php 

	/**
	 *
	 **/

?>
	
		<div id="filters-container-left" class="col-xs-12 clearfix filter-box">
			<div id="filter-selections" class="clearfix">

			<div class="search-wrapper">
			<input type="text" id="eci-search"  required class="search-box" placeholder="Search keyword..."  />
			<a class="close-icon" id="eci-search-clear"><i class="fa fa-times-circle fa-2x"></i></a>
			</div>

			<fieldset class="collapsible" style="background: #FCF1D6;">
			<legend><h6><i class="fa fa-chevron-up icon"></i> MY COMMUNITY</h6></legend>
			<div>

			<ul id="filters-container-regions" class="list-group">
			</ul>

			<select id="list-geography" class="form-control">
			</select>

			<ul id="filter-geography">
			</ul>
			</div>
			</fieldset>

			<fieldset class="collapsible">
			<legend><h6><i class="fa fa-chevron-up icon"></i> ENGAGEMENT IMPACT AREA</h6></legend>
			<div>
			<ul id="filter-theme" data-type="theme">
			</ul>
			</div>
			</fieldset>

			<fieldset class="collapsible">
			<legend><h6><i class="fa fa-chevron-down icon"></i> ENGAGEMENT TYPE</h6></legend>
			<div>
			<ul id="filter-type" data-type="type">
			</ul>
			</div>
			</fieldset>

			<?php if( is_user_logged_in() ) { ?>
			<fieldset class="collapsible">
			<legend><h6><i class="fa fa-chevron-down icon"></i> AFFILIATION</h6></legend>
			<div>
			<ul id="filter-affiliation" data-type="affiliation">
			</ul>
			</div>
			</fieldset>
			<?php } ?>

			<fieldset class="collapsible" id="style-container">
			<legend><h6><i class="fa fa-chevron-up icon"></i> DISPLAY STYLE</h6></legend>
			<div class="display-style">
			<i class="fa fa-th fa-3x active" title="Grid View" id="grid-view"></i>
			<i class="fa fa-list fa-3x" title="List View" id="list-view"></i>
			</div>
			</fieldset>
			
				<div class='side-menu'>
				<?php if( !is_user_logged_in() ) { ?>
					<a class="nolist-link" href="<?php echo get_home_url(); ?>/wp-login.php?action=use-sso">Log In</a>
				<?php } else { 
					echo "<h4>Go To:</h4>";
					 wp_nav_menu( array(
						'theme_location' => 'top-logged-in',
						'menu_id'        => 'top-menu-logged-in',
					) );
				} ?>
				</div>
			</div>
			
			
		</div>
			