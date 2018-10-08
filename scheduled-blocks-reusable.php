<?php
/**
 * Scheduled Blocks Reusable Add-on
 *
 * @package     scheduled-blocks-reusable
 * @author      Richard Tape
 * @copyright   2018 Richard Tape
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:  Scheduled Blocks Reusable Blocks Add-On
 * Plugin URI:   https://scheduledblocks.com/add-ons/reusable
 * Description:  Schedule when your reusable blocks go live. An add-on for Scheduled Blocks.
 * Version:      0.1.2
 * Author:       Richard Tape
 * Requires PHP: 7
 * Author URI:   https://scheduledblocks.com/
 * Text Domain:  scheduled-blocks-reusable
 * License:      GPL-3.0+
 * License URI:  http://www.gnu.org/licenses/gpl-3.0.txt
 * Icon1x:       https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/icon-128x128.png
 * Icon2x:       https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/icon-256x256.png
 * BannerHigh:   https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/banner-1544x500.png
 * BannerLow:    https://raw.githubusercontent.com/froger-me/wp-plugin-update-server/master/examples/banner-722x250.png
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load our required files.
require_once plugin_dir_path( __FILE__ ) . 'lib/class-scheduled-blocks-reusable.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/wp-package-updater/class-wp-package-updater.php';

$shceduled_blocks_reusable = new WP_Package_Updater(
	'https://scheduledblocksdotcom.local',
	wp_normalize_path( __FILE__ ),
	wp_normalize_path( plugin_dir_path( __FILE__ ) ),
	true
);

/**
 * Initialize ourselves!
 *
 * @return void
 */
function plugins_loaded__scheduled_blocks_reusable_init() {

	$scheduled_blocks_reusable_go = new Scheduled_Blocks_Reusable();
	$scheduled_blocks_reusable_go->init();

}// end plugins_loaded__scheduled_blocks_reusable_init()

add_action( 'plugins_loaded', 'plugins_loaded__scheduled_blocks_reusable_init', 12 );
