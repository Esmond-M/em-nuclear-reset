<?php
/**
 * Plugin Name: EM Nuclear Reset
 * Description: Developer utility for resetting a single WordPress installation.
 * Version: 0.1.0
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Author: Esmond McCain
 * License: GPL-2.0-or-later
 * Text Domain: em-nuclear-reset
 *
 * @package EsmondMcCain\NuclearReset
 */

defined( 'ABSPATH' ) || exit;

define( 'EM_NUCLEAR_RESET_VERSION', '0.1.0' );
define( 'EM_NUCLEAR_RESET_FILE', __FILE__ );
define( 'EM_NUCLEAR_RESET_DIR', plugin_dir_path( __FILE__ ) );
define( 'EM_NUCLEAR_RESET_URL', plugin_dir_url( __FILE__ ) );

require_once EM_NUCLEAR_RESET_DIR . 'includes/Preflight.php';
require_once EM_NUCLEAR_RESET_DIR . 'includes/FilesystemPreflight.php';
require_once EM_NUCLEAR_RESET_DIR . 'includes/Confirmation.php';
require_once EM_NUCLEAR_RESET_DIR . 'includes/OperationLog.php';
require_once EM_NUCLEAR_RESET_DIR . 'includes/AdminPage.php';

add_action( 'admin_menu', array( '\\EsmondMcCain\\NuclearReset\\AdminPage', 'register' ) );
