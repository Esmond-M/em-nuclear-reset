<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/fixtures/wordpress/' );
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'https://example.test/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback ) {
		return ! empty( $hook_name ) && is_callable( $callback );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( $number, $decimals, '.', ',' );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}
}

require_once dirname( __DIR__ ) . '/em-nuclear-reset.php';
