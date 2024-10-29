<?php
/**
 * WPJM tab
 *
 * @author 		BePro Listings
 * @package 	bepro_listings/Templates
 */

 
 
 $data = get_option("bepro_listings_wpjm");

 ?>
 <style type="text/css">

 </style>

 <div class="panel entry-content" id="tab-wpjm">
	<h2><?php echo  $data["heading"]; ?></h2>
	<div id="bepro_listings_wpjm_div">
		<ul>
		<?php
			if(empty($jobs))
				echo $data["no_jobs"];
			else
				foreach($jobs as $job)
					echo "<li><a href='".$job->guid."'>".$job->post_title."</li>";
				
		?>
		</ul>
	</div> 
 </div>