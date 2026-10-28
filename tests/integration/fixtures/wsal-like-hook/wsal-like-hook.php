<?php
/**
 * Plugin Name: WSAL-Like Hook Fixture
 * Description: Integration fixture for deferred plugin deletion.
 * Version: 1.0.0
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = 'WSAL_Like_Hook\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$class_file = __DIR__ . '/includes/' . substr( $class_name, strlen( $prefix ) ) . '.php';

		require $class_file;
	}
);

add_action(
	'wp_install',
	static function () {
		new \WSAL_Like_Hook\DateTime_Formatter_Helper();
	},
	10,
	0
);