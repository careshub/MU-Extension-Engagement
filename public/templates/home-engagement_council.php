<?php 

/**
 * The template for displaying the home page
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Engagement_Council
 * @since 1.0
 * @version 1.0
 */

	get_header('eci'); ?>

<div class="eci-wrap contentclass" role="document">
	<?php
		\MU_Ext_Engagement\Public_Facing\get_template_part( 'home', 'filters-left' );
		echo '<input type="hidden" id="plugin-file-path" value="' . plugin_dir_url(dirname(__FILE__)) . '" />';
	?>
	
		<ul class="secondary-menu">
			<?php
			$ecpp = get_ecpp();
			foreach( $ecpp as $geog => $info_array ){
				$active_class = ($info_array['id'] == "ecpp_county" ) ? " active " : "";
			?>
				<li id="<?php echo $info_array['id']; ?>" class="ecpp-geog <?php echo $active_class; ?>" ><?php echo $geog; ?> </li>
				
			<?php
			} ?>
		</ul>

    <div id="content" class="container homepagecontent ecpp open-leftmenu" style="padding-top:0px;">
   		<div class="row">
          	<div class="main col-md-12" role="main">
				<div class="content-outermargin" itemprop="mainContentOfPage" itemscope itemtype="http://schema.org/WebPageElement">

					<?php
						\MU_Ext_Engagement\Public_Facing\get_template_part( 'home', 'map-selector' );
					?>
					
					<div class="row" id="content-container">
						<div id="impact-container" class="col-xs-12 clearfix engagements-box">
							<div id="engage-title">
								<h3>UM System Impact</h3>
							</div>

							<div id="impact-list">
							</div>
						</div>

						<div  id="engagement-container" class="col-xs-12 clearfix engagements-box">
							<div id="engage-title">
								<h3>MU Engagement</h3>
							</div>

							<div id="engage-loading">
							<i class="fa fa-spinner fa-spin fa-2x"></i> Loading...
							</div>

							<div class="row" id="chart-container">
								<div id="engage-subtitle">
									<h4 class="col-xs-12 modal-theme">Engagements by Impact Areas</h4>
									<div id="engage-geog">State of Missouri</div>
								</div>

									<div class="col-xs-12">
										<div id="overview-chart" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
									</div>
							</div>

							<div id="engage-list">
							</div>	
						</div>
					</div>
			</div>
			
			
		</div><!-- /.main -->
	</div>

		<!-- Modal -->
	<div class="modal fade" id="single-engagement-modal" tabindex="-1" role="dialog" aria-labelledby="single-engagement-modal-msg" aria-hidden="true">
		
		<div class="col-sm-1 hidden-xs"></div>
		<div class="modal-dialog col-xs-12 col-sm-10" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title" id="exampleModalLabel">Modal title</h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-theme" id="single-engagement-theme"></h4>
				</div>
				<div class="modal-body row">
					<!--<div class="col-sm-1 hidden-xs">
						<button class="prev-btn btn btn-warning">Prev</button>
					</div>-->
					<div class="modal-main col-xs-12">

					</div>
					<!--<div class="col-sm-1 hidden-xs row">
						<button class="next-btn btn btn-primary">Next</button>
					</div>-->
			
				</div>
				<div class="modal-footer">
					<div class="h">
						<button class="prev-btn btn btn-warning">Prev</button>
						<button class="next-btn btn btn-primary">Next</button>
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>
					<!--<div class="hidden-xs">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>-->
				</div>
			</div>
		</div>
		<div class="col-sm-1 hidden-xs"></div>
	</div>



</div> <!-- /#content -->

</div> <!-- /.eci-wrap -->
		
<?php get_footer();