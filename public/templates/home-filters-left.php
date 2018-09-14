<?php 

	/**
	 *
	 **/

?>
	
		<div id="filters-container-left" class="col-xs-12 clearfix filter-box">
			<div id="filter-selections" class="clearfix">

			<div class="collapsible-section">
			<div class="collapsible-section-title" >
			<i class="fa pull-right fa-chevron-up"></i>
			KEYWORD SEARCH
			</div>
			<div id="filter-keyword" class="expand-view">
			<div class="search-wrapper">
			<input type="text" id="eci-search" required class="search-box" placeholder="Search"  />
			<a class="close-icon" id="eci-search-clear"><i class="fa fa-times-circle fa-2x"></i></a>
			</div>
			</div>
			</div>

			<div class="collapsible-section">
			<div class="collapsible-section-title" style="background: #FCF1D6;" >
				<i class="fa pull-right fa-chevron-up"></i>
				MY COMMUNITY
			</div>
			<div class="expand-view">
			<ul id="filters-container-regions" class="list-group">
			</ul>

			<select id="list-geography" class="form-control">
			</select>

			<ul id="filter-geography">
			</ul>
			</div>
			</div>

			<div class="collapsible-section">
			<div class="collapsible-section-title" >
			<i class="fa pull-right fa-chevron-up"></i>
			ENGAGEMENT IMPACT AREA
			</div>
			<div class="expand-view">
			<ul id="filter-theme" data-type="theme">
			</ul>
			</div>
			</div>


			<div class="collapsible-section">
			<div class="collapsible-section-title" >
			<i class="fa pull-right fa-chevron-up"></i>
			ENGAGEMENT TYPE
			</div>
			<div class="expand-view">
			<ul id="filter-type" data-type="type">
			</ul>
			</div>
			</div>

			<div class="collapsible-section">
			<div class="collapsible-section-title">
			<i class="fa pull-right fa-chevron-up"></i>
			AFFILIATION
			</div>
			<div class="expand-view">
			<ul id="filter-affiliation" data-type="affiliation">
			</ul>
			</div>
			</div>

			<div class="collapsible-section">
			<div class="collapsible-section-title">
			<i class="fa pull-right fa-chevron-up"></i>
			DISPLAY STYLE
			</div>
			<div class="expand-view display-style">
			<i class="fa fa-th fa-3x active" title="Grid View" id="grid-view"></i>
			<i class="fa fa-list fa-3x" title="List View" id="list-view"></i>
			</div>
			</div>
			

			<div class='side-menu'>
				<a class="nolist-link" id="clear-filters" href="javascript:void(0);">Clear Filters</a>
			</div>

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
			