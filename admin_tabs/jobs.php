<?php
/**
 * Contact tab
 *
 * @author 		BePro Listings
 * @package 	bepro_listings_contact/admin_tabs
 */


 $data = get_option("bepro_listings_wpjm");
 ?>

 <div class="panel entry-content" id="tab-wpjm">
	<h2>Frontend Tab Options</h2>
	<div id="bepro_listings_wpjm_form_div">
		<form class='admin_addon_form' id="bepro_listings_wpjm">
			<input type="hidden" name="action" value="bepro_ajax_save_wpjm_settings">
			<H3>WPJM</H3>
			<span class="form_label">Frontend Tab Heading Text</span><input type="text" id="heading" name="heading" placeholder="Related Jobs" value="<?php echo $data["heading"]; ?>" />
			<p>&nbsp;</p>
			<span class="form_label">Text when no Jobs</span><input type="text" id="no_jobs" name="no_jobs" placeholder="No Jobs Currently" value="<?php echo $data["no_jobs"]; ?>" /><p>&nbsp;</p>
			<span class="form_label">Show on frontend?</span><input type="checkbox" value="1" name="show_on_page" <?php echo $data["show_on_page"]? "checked='checked'":""; ?>><p>&nbsp;</p>
			<input type="submit" value="Save BPL WPJM Settings">
		</form>
	</div> 
 </div>