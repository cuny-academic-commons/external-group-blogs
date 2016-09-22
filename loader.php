<?php
/*
Plugin Name: External Group Blogs
Plugin URI: http://wordpress.org/plugins/external-group-blogs/
Description: Allows group creators to supply external blog RSS feeds that will attach future posts on blogs to group activity.
Version: 1.6.1
Requires at least: WordPress 3.5 / BuddyPress 1.7
Tested up to: WordPress 4.6 / BuddyPress 2.6
License: GNU/GPL 2
Author: Andy Peatling
Contributors: apeatling, modemlooper, boonebgorges, r-a-y
Author URI: http://profiles.wordpress.org/apeatling/
*/

/**
 * Only load the plugin functions if BuddyPress is loaded and initialized.
 */
function bp_groupblogs_init() {
	require( dirname( __FILE__ ) . '/includes/bp-groups-externalblogs.php' );
}
add_action( 'bp_init', 'bp_groupblogs_init' );

/**
 * Load translations for this plugin.
 */
function bp_groupblogs_load_translations() {
	load_plugin_textdomain( 'bp-groups-externalblogs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'bp_groupblogs_load_translations' );

/**
 * On activation, register our cron hook to refresh external blog posts.
 */
function bp_groupblogs_activate() {
	wp_schedule_event( time(), 'hourly', 'bp_groupblogs_cron' );
}
register_activation_hook( __FILE__, 'bp_groupblogs_activate' );

/**
 * On deactivation, clear the cron and delete all group RSS activity.
 */
function bp_groupblogs_deactivate() {
	wp_clear_scheduled_hook( 'bp_groupblogs_cron' );

	/* Remove all external blog activity */
	if ( function_exists( 'bp_activity_delete' ) ) {
		bp_activity_delete( array( 'type' => 'exb' ) );
	}
}
register_deactivation_hook( __FILE__, 'bp_groupblogs_deactivate' );
