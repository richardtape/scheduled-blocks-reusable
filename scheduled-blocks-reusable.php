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
 * Version:      0.1.1
 * Author:       Richard Tape
 * Requires PHP: 7
 * Author URI:   https://scheduledblocks.com/
 * Text Domain:  scheduled-blocks-reusable
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load our required files.
require_once 'lib/class-scheduled-blocks-reusable.php';

/**
 * Initialize ourselves!
 *
 * @return void
 */
function plugins_loaded__scheduled_blocks_reusable_init() {

	$scheduled_blocks_reusable = new Scheduled_Blocks_Reusable();
	$scheduled_blocks_reusable->init();

}// end plugins_loaded__scheduled_blocks_reusable_init()

add_action( 'plugins_loaded', 'plugins_loaded__scheduled_blocks_reusable_init', 12 );
