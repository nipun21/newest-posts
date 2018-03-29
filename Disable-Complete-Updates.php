<?php
/**
 * The main plugin file
 *
 * @package WordPress_Plugins
 * @subpackage Disable_Complete_Updates
 */

/*
Plugin Name: Disable Complete WP Updates
Description: Disables the theme, plugin and core update checking, the related cronjobs and notification system.
Plugin URI:  https://wordpress.org/plugins/disable-complete-wp-updates/ 
Version:     1.0.0
Author:      Nipun Tyagi
Author URI:  https://profiles.wordpress.org/nipun21
License:	 GPL3

Copyright 2018 Nipun Tyagi (email : nipuntyagi20@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/



/**
 * Define the plugin version   
 */
define("DCWPVNUPD", "1.0.0");


/**
 * The Disable_Complete_Updates class
 *
 * @package 	WordPress_Plugins
 * @subpackage 	Disable_Complete_Updates
 * @since 		4.9.4
 * @author 		nipuntyagi20@gmail.com
 */
class Disable_Complete_Updates {
	/**
	 * The Disable_Complete_Updates class constructor
	 * initializing required stuff for the plugin
	 *
	 * PHP 5 Constructor
	 *
	 * @since 		4.9.4
	 * @author 		nipuntyagi20@gmail.com
	 */
	function __construct() {
		add_action( 'admin_init', array($this, 'admin_init') );
		
		
		/*
		 * Disable Core Updates
		 * 3.0 to 4.9.4
		 */
		add_filter( 'pre_transient_update_core', array($this, 'last_check_dtm') );
		/*
		 * 4.9.4
		 */
		add_filter( 'pre_site_transient_update_core', array($this, 'last_check_dtm') );
		
		

		/*
		 * Disable Theme Updates
		 * 3.0 to 4.9.4
		 */
		add_filter( 'pre_transient_update_themes', array($this, 'last_check_dtm') );
		/*
		 * 4.9.4
		 */
		add_filter( 'pre_site_transient_update_themes', array($this, 'last_check_dtm') );


		/*
		 * Disable Plugin Updates
		 * 3.0 to 4.9.4
		 */
		add_action( 'pre_transient_update_plugins', array($this, 'last_check_dtm') );
		/*
		 * 4.9.4
		 */
		add_filter( 'pre_site_transient_update_plugins', array($this, 'last_check_dtm'));


		
		
		
		/*
		 * Filter schedule checks
		 *
		 * @link https://wordpress.org/support/topic/possible-performance-improvement/#post-8970451
		 */
		add_action('schedule_event', array($this, 'filter_cron_dtm'));


		/*
		 * Disable All Automatic Updates
		 * 4.8+
		 *
		 * @author	nipuntyagi20@gmail.com
		 */
		add_filter( 'auto_update_translation', '__return_false' );
		add_filter( 'automatic_updater_disabled', '__return_true' );
		add_filter( 'allow_minor_auto_core_updates', '__return_false' );
		add_filter( 'allow_major_auto_core_updates', '__return_false' );
		add_filter( 'allow_dev_auto_core_updates', '__return_false' );
		add_filter( 'auto_update_core', '__return_false' );
		add_filter( 'wp_auto_update_core', '__return_false' );
		add_filter( 'auto_core_update_send_email', '__return_false' );
		add_filter( 'send_core_update_notification_email', '__return_false' );
		add_filter( 'auto_update_plugin', '__return_false' );
		add_filter( 'auto_update_theme', '__return_false' );
		add_filter( 'automatic_updates_send_debug_email', '__return_false' );
		add_filter( 'automatic_updates_is_vcs_checkout', '__return_true' );


		add_filter( 'automatic_updates_send_debug_email ', '__return_false', 1 );
		if( !defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) define( 'AUTOMATIC_UPDATER_DISABLED', true );
		if( !defined( 'WP_AUTO_UPDATE_CORE') ) define( 'WP_AUTO_UPDATE_CORE', false );

		add_filter( 'pre_http_request', array($this, 'blocked_request_dtm'), 10, 3 );
	}


	/**
	 * Initialize and load the plugin stuff
	 *
	 * @since 		4.9.4
	 * @author 		nipuntyagi20@gmail.com
	 */
	function admin_init() {
		if ( !function_exists("remove_action") ) return;
		
		/*
		 * Remove 'update plugins' option from bulk operations select list
		 */
		global $current_user;
		$current_user->allcaps['update_plugins'] = 0;
		
		/*
		 * Hide maintenance and update nag
		 */
		remove_action( 'admin_notices', 'update_nag', 3 );
		remove_action( 'network_admin_notices', 'update_nag', 3 );
		remove_action( 'admin_notices', 'maintenance_nag' );
		remove_action( 'network_admin_notices', 'maintenance_nag' );
		

				/*
		 * Disable Core Updates
		 * 3.0 to 4.9.4
		 */
		add_filter( 'pre_option_update_core', '__return_null' );

		remove_action( 'wp_version_check', 'wp_version_check' );
		remove_action( 'admin_init', '_maybe_update_core' );
		wp_clear_scheduled_hook( 'wp_version_check' );


		/*
		 * 4.9.4
		 */
		wp_clear_scheduled_hook( 'wp_version_check' );

		
		
		/*
		 * Disable Theme Updates
		 * 3.0 to 4.9.4
		 */
		remove_action( 'load-themes.php', 'wp_update_themes' );
		remove_action( 'load-update.php', 'wp_update_themes' );
		remove_action( 'admin_init', '_maybe_update_themes' );
		remove_action( 'wp_update_themes', 'wp_update_themes' );
		wp_clear_scheduled_hook( 'wp_update_themes' );


		/*
		 * 4.9.4
		 */
		remove_action( 'load-update-core.php', 'wp_update_themes' );
		wp_clear_scheduled_hook( 'wp_update_themes' );


		/*
		 * Disable Plugin Updates
		 * 3.0 to 4.9.4
		 */
		remove_action( 'load-plugins.php', 'wp_update_plugins' );
		remove_action( 'load-update.php', 'wp_update_plugins' );
		remove_action( 'admin_init', '_maybe_update_plugins' );
		remove_action( 'wp_update_plugins', 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_plugins' );

		/*
		 * 4.9.4
		 */
		remove_action( 'load-update-core.php', 'wp_update_plugins' );
		wp_clear_scheduled_hook( 'wp_update_plugins' );



		/*
		 * 4.8+
		 */
		remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );
		remove_action( 'admin_init', 'wp_maybe_auto_update' );
		remove_action( 'admin_init', 'wp_auto_update_core' );
		wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
	}




	/**
	 * Check the outgoing request
	 *
	 * @since 		4.8.2
	 */
	public function blocked_request_dtm($pre, $args, $url) {
		/* Empty url */
		if( empty( $url ) ) {
			return $pre;
		}

		/* Invalid host */
		if( !$host = parse_url($url, PHP_URL_HOST) ) {
			return $pre;
		}

		$url_data = parse_url( $url );

		/* block request */
		if( false !== stripos( $host, 'api.wordpress.org' ) && (false !== stripos( $url_data['path'], 'update-check' ) || false !== stripos( $url_data['path'], 'browse-happy' )) ) {
			return true;
		}

		return $pre;
	}


	/**
	 * Filter cron events
	 *
	 * @since 		4.8.2
	 */
	public function filter_cron_dtm($event) {
		switch( $event->hook ) {
			case 'wp_version_check':
			case 'wp_update_plugins':
			case 'wp_update_themes':
			case 'wp_maybe_auto_update':
			$event = false;
			break;
		}
		return $event;
	}
	
	
	/**
	 * Override version check info
	 *
	 * @since 		4.8.2
	 */
	public function last_check_dtm( $t ) {
		include( ABSPATH . WPINC . '/version.php' );
		
		$current = new stdClass;
		$current->updates = array();
		$current->version_checked = $wp_version;
		$current->last_checked = time();
		
		return $current;
	}
}

if ( class_exists('Disable_Complete_Updates') ) {
	$Disable_Complete_Updates = new Disable_Complete_Updates();
}
?>