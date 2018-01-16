<?php 

	/**
	 *
	 **/

?>
	
		<div id="filters-container-left" class="col-xs-12 clearfix filter-box">
			<div id="filter-selections" class="clearfix">

			<select id="filters-container-regions" class="hidden">
			<?php
				$ecpp = get_ecpp();
				foreach( $ecpp as $geog => $info_array ){
					$active_class = ($info_array['id'] == "ecpp_county" ) ? " active " : "";
				?>
					<option id="<?php echo $info_array['id']; ?>" class="ecpp-geog-option <?php echo $active_class; ?>" ><?php echo $geog; ?> </li>
					
				<?php
				} 
			?>
			</select>

			<fieldset class="collapsible">
			<legend><h6><i class="fa fa-chevron-up icon"></i> MY COMMUNITY</h6></legend>
			<div>
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

			<div id="filter-container11">
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
			</div>
			
			<?php if( !is_user_logged_in() ) { ?>
			<a href="<?php echo get_home_url(); ?>/wp-login.php?action=use-sso">Log In</a>
			<?php } ?>
			</div>
			
			
		</div>
			