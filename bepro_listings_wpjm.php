<?php
/*
Plugin Name: BePro Listings WPJM
Plugin Script: bepro_listings_wpjm.php
Plugin URI: http://www.beprosoftware.com/shop
Description: Manage WP Job Manager companies with BePro Listings
Version: 1.0.01
License: GPL V3
Author: BePro Software Team
Author URI: http://www.beprosoftware.com


Copyright 2012 [Beyond Programs LTD.](http://www.beyondprograms.ca/)

Commercial users are requested to, but not required to contribute, promotion, 
know-how, or money to plug-in development or to www.beprosoftware.com. 

This file is part of BePro Listings.

BePro Listings is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

BePro Listings is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with BePro Listings.  If not, see <http://www.gnu.org/licenses/>.
*/

if ( !defined( 'ABSPATH' ) ) return;

class bpl_wpjm_link{
	function __construct(){
		//actions
		add_action( 'delete_post', array($this, 'wpjm_delete_related_job_listings'));
		add_action( 'init', array($this, 'initialize_vars'));
		add_action( 'wp_ajax_bepro_ajax_save_wpjm_settings', array($this, 'save_wpjm_settings') );
		
		//front end
		add_action("bepro_listings_tabs", array($this, "jobs_tab"), 40);
		add_action("bepro_listings_tab_panels", array($this, "jobs_tab_panel"), 40);
		
		//admin
		add_action("bepro_listings_admin_tabs", array($this, "admin_tab"), 100);
		add_action("bepro_listings_admin_tab_panels", array($this, "admin_tab_panel"), 100);
		add_action('admin_init', array($this, 'child_plugin_has_parent_plugin'), 100);
		
		
		// filters
		add_filter('submit_job_form_fields', array( $this, 'wpjm_alter_form_fields'), 1); // alter the job fields 
		add_filter('init', array( $this, 'wpjm_alter_posted_field_values'), 1); // alter the job fields 
		add_filter('wp_insert_post', array( $this, 'wpjm_bpl_link'), 1, 3);// alter the job fields 
		add_filter('submit_job_form_fields_get_job_data', array( $this, 'wpjm_alter_ediable_field_values'), 1, 2); // alter the job fields 
		
	}
	
	function wpjm_bpl_link($job_id, $post, $update){
		global $wpdb;
		if(empty($_POST["submit_job"]) || empty($_POST["company_select"]))
			return;

		$post_id = is_numeric($_POST["company_select"])? filter_var($_POST["company_select"], FILTER_SANITIZE_NUMBER_INT):"";
		
		if(is_numeric($job_id) && is_numeric($post_id) && ($post->post_type == "job_listing"))
			$wpdb->query("INSERT INTO ".$wpdb->prefix."bepro_listings_wpjm (bpl_id, wpjm_id) values($post_id, $job_id)");

	}
	
	function initialize_vars(){
		global $wpdb;
		if ( !defined( 'BPL_WPJM_TABLE_NAME' ) )
			define( 'BPL_WPJM_TABLE_NAME', "bepro_listings_wpjm" );
		if ( !defined( 'BPL_WPJM_TABLE' ) )
			define( 'BPL_WPJM_TABLE', $wpdb->prefix.BPL_WPJM_TABLE_NAME);
			
		define( 'BPL_WPJM_UPDATE_SLUG', "bl_wpjm_kdfspoijWEPRJadfpojerw" );
		define( 'BPL_WPJM_UPDATE_FILE', basename(dirname(__FILE__)) );
			
		$data = get_option("bepro_listings_wpjm");
		if(empty($data)){
			$data["show_on_page"] = 1;
			$data["heading"] = "Jobs :";
			$data["no_jobs"] = "No Jobs for this location";
			update_option("bepro_listings_wpjm", $data);
		}
	}
	
	//change the wpjm form fields before they are shown to the user
	function wpjm_alter_form_fields($fields){
		global $wpdb;
		
		//don't alter the fields if the form is submitted
		if(!empty( $_POST['submit_job']))
			return $fields;
		
		//get current user
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		
		//get user's companies
		$companies = $wpdb->get_results("SELECT wp_posts.ID, wp_posts.post_title FROM ".$wpdb->prefix.BEPRO_LISTINGS_TABLE_NAME." as geo 
				LEFT JOIN ".$wpdb->prefix."posts as wp_posts on wp_posts.ID = geo.post_id WHERE wp_posts.post_status != 'trash' AND wp_posts.post_author = ".$user_id." ORDER BY wp_posts.post_title asc");
		$my_companies = array();
		
		foreach($companies as $company)
			$my_companies[$company->ID] = $company->post_title;
				
		//remove job->location
		//unset($fields["job"]["job_location"]);
		//remove company ->*
		unset($fields["company"]["company_name"]);
		unset($fields["company"]["company_website"]);
		unset($fields["company"]["company_twitter"]);
		unset($fields["company"]["company_logo"]);
		//push company select box
		$fields["company"]["company_select"]  = array (
										'label'       => __( 'Select Company', 'wp-job-manager' ),
										'type'        => "select",
										'required'    => true,
										'placeholder' => __( 'Choose Company&hellip;', 'wp-job-manager' ),
										'priority'    => 1,
										'default'     => '',
										'description' => 'Your created companies can be selected here',
										'options' => $my_companies
									);
		return $fields;
	}
	
	function wpjm_alter_posted_field_values(){
		global $wpdb;
		
		if(empty( $_POST['company_select']))
			return;
		//add job->location from BPL ID
		//add company ->* from BPL ID
		
		//remove BPL company select box
		
		//get current user
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$post_id = filter_var($_POST["company_select"], FILTER_SANITIZE_NUMBER_INT);
		
		
		//get user's companies
		$my_company = $wpdb->get_row("SELECT wp_posts.ID, wp_posts.post_title, geo.* FROM ".$wpdb->prefix.BEPRO_LISTINGS_TABLE_NAME." as geo 
				LEFT JOIN ".$wpdb->prefix."posts as wp_posts on wp_posts.ID = geo.post_id WHERE wp_posts.post_status != 'trash' AND wp_posts.post_author = ".$user_id." AND wp_posts.ID=".$post_id);
				
		$address = $my_company->city.", ".$my_company->state.", ".$my_company->country.", ".$my_company->postcode;
		empty($_POST["job_location"])? $address:"";
		//remove company ->*
		$_POST["company_name"] =  $my_company->post_title;
		$_POST["company_website"] = get_permalink($my_company->post_id);
		$_POST["current_company_logo"] = get_post_thumbnail_id($my_company->post_id);
		
	}
	
	//edit field before its shown
	function wpjm_alter_ediable_field_values($fields, $job){
		global $wpdb;
		//add the BPL ID to the company select box for this WPJM Job ID beofre the field is shown on page
		//get current user
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		
		$wpjm_bpl_id =  $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."bepro_listings_wpjm WHERE wpjm_id=".$job->ID);

		//get user's companies
		$companies = $wpdb->get_results("SELECT wp_posts.ID, wp_posts.post_title FROM ".$wpdb->prefix.BEPRO_LISTINGS_TABLE_NAME." as geo 
				LEFT JOIN ".$wpdb->prefix."posts as wp_posts on wp_posts.ID = geo.post_id WHERE wp_posts.post_status != 'trash' AND wp_posts.post_author = ".$user_id." ORDER by wp_posts.post_title asc");
		$my_companies = array();
		
		foreach($companies as $company)
			$my_companies[$company->ID] = $company->post_title;

				
		//remove job->location
		//unset($fields["job"]["job_location"]);
		//remove company ->*
		unset($fields["company"]["company_name"]);
		unset($fields["company"]["company_website"]);
		unset($fields["company"]["company_twitter"]);
		unset($fields["company"]["company_logo"]);
		//push company select box
		$fields["company"]["company_select"]  = array (
										'label'       => __( 'Select Company', 'wp-job-manager' ),
										'type'        => "select",
										'required'    => true,
										'placeholder' => __( 'Choose Company&hellip;', 'wp-job-manager' ),
										'priority'    => 1,
										'default'     => $wpjm_bpl_id->bpl_id,
										'value'     => $wpjm_bpl_id->bpl_id,
										'description'     => 'Your created companies can be selected here',
										'options' => $my_companies
									);
		return $fields;
	}
	
	//if a company is deleted then delete all of its jobs
	function wpjm_delete_related_job_listings(){
		
	}
	
	//FRONT END
	
	function jobs_tab(){
		include(plugin_dir_path( __FILE__ )."tabs/tab-jobs.php");
	}
	
	function jobs_tab_panel(){
		global $post, $wpdb;      

		$job_array = array();		
		//get all job id's related to this BePro Listing
		$job_links = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."bepro_listings_wpjm WHERE bpl_id=".$post->ID); 
		//put the ID's in an array for the get_post() function
		foreach($job_links as $job_link){
			$job_array[] = $job_link->wpjm_id;
		}

		//get all jobs and their details
		$jobs = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."posts WHERE ID in(".implode(",", $job_array).") AND (post_status='publish' || post_status='inherit')");

		//engage the template
		include(plugin_dir_path( __FILE__ )."tabs/jobs.php");
	}
	
	
	//ADMIN
	
	function admin_tab(){
		include(plugin_dir_path( __FILE__ )."admin_tabs/tab-jobs.php");
	}
	function admin_tab_panel(){
		include(plugin_dir_path( __FILE__ )."admin_tabs/jobs.php");
	}
	
	function save_wpjm_settings(){
		if(!is_admin()){
			echo 0;
			exit;
		}
		
		$data["heading"] = sanitize_text_field($_POST["heading"]);
		$data["no_jobs"] = sanitize_text_field($_POST["no_jobs"]);
		$data["show_on_page"] = filter_var($_POST["show_on_page"], FILTER_SANITIZE_NUMBER_INT);
		if(update_option("bepro_listings_wpjm", $data))
			echo 1;
		else
			echo 0;
		
		exit;
	}
	
		
	/*
	//
	//
	// SETUP
	//
	//
	*/
	//activate
	function bpl_wpjm_activate() {
		global $wpdb;  
		ob_start();
		if (function_exists('is_multisite') && is_multisite()){ 
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM $wpdb->blogs"));
			foreach($blogids as $blogid_x){
				bpl_wpjm_link::bpl_wpjm_install_table($blogid_x);
			}
		}else{
			bpl_wpjm_link::bpl_wpjm_install_table();
		}
		$value = ob_get_contents();
		ob_end_clean();
	}
	
	//Setup database for multisite
	function  bpl_wpjm_install_table($blog_id = false) {
		global $wpdb;
		bpl_wpjm_link::initialize_vars();
		
		//Manage Multi Site
		if($blog_id && ($blog_id != 1)){
			$table_name = $wpdb->prefix.$blog_id."_".BPL_WPJM_TABLE_NAME;
		}else{
			$table_name = $wpdb->prefix.BPL_WPJM_TABLE_NAME;
		}
		
 		if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'")!=$table_name) {

			$sql = "CREATE TABLE " . $table_name . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				bpl_id int(9) NOT NULL,
				wpjm_id int(9) NOT NULL,
				created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				CONSTRAINT uc_bpl_wpjm UNIQUE (bpl_id , wpjm_id),
				PRIMARY KEY (id)
			);";

			require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
			dbDelta($sql);
			
		}
		
		
		
		/*load default options if they dont already exist	*/	
		$data = get_option("bepro_listings_wpjm");
		if(empty($data)){
			$data["show_on_page"] = 1;
			$data["heading"] = "Jobs :";
			$data["no_jobs"] = "No Jobs for this location";
			update_option("bepro_listings_wpjm", $data);
		}
		
		
	}
	
	
	function child_plugin_has_parent_plugin() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'bepro-listings/bepro_listings.php' ) ) {
			add_action( 'admin_notices',  array($this,'bepro_listings_plugin_notice') );

			deactivate_plugins( plugin_basename( __FILE__ ) ); 

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
		
		if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'wp-job-manager/wp-job-manager.php' ) ) {
			add_action( 'admin_notices',  array($this,'wp_job_manager_plugin_notice') );

			deactivate_plugins( plugin_basename( __FILE__ ) ); 

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}

	function bepro_listings_plugin_notice(){
		?><div class="error notice notice-warning is-dismissible"><p>Sorry, but BePro Listings WPJM requires <a href="https://wordpress.org/plugins/bepro-listings/">BePro Listings</a> to be installed and active.</p></div><?php
	}
	
	function wp_job_manager_plugin_notice(){
		?><div class="error notice notice-warning is-dismissible"><p>Sorry, but BePro Listings WPJM requires <a href="https://wordpress.org/plugins/wp-job-manager/">WP Job Manager</a> to be installed and active.</p></div><?php
	}
	
}


register_activation_hook( __FILE__, array( 'bpl_wpjm_link', 'bpl_wpjm_activate' ) );
$i_like_this = new bpl_wpjm_link();
?>