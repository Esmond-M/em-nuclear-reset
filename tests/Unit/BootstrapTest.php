<?php

namespace EsmondMcCain\NuclearReset\Tests\Unit;

use PHPUnit\Framework\TestCase;
use EsmondMcCain\NuclearReset\Preflight;

class BootstrapTest extends TestCase {
	public function test_plugin_constants_are_defined(): void {
		self::assertSame( '0.1.0', EM_NUCLEAR_RESET_VERSION );
		self::assertFileExists( EM_NUCLEAR_RESET_FILE );
		self::assertDirectoryExists( EM_NUCLEAR_RESET_DIR );
	}

	public function test_preflight_only_keeps_the_active_installation_prefix(): void {
		$tables = Preflight::filter_prefixed_tables(
			array(
				'client_posts',
				'wp_options',
				'wp_posts',
				'wpfoo_options',
				'wp_custom_data',
			),
			'wp_'
		);

		self::assertSame(
			array(
				'wp_custom_data',
				'wp_options',
				'wp_posts',
			),
			$tables
		);
	}

	public function test_preflight_reports_missing_core_tables(): void {
		$missing = Preflight::get_missing_core_tables(
			array(
				'wp_options',
				'wp_users',
			),
			'wp_'
		);

		self::assertNotContains( 'wp_options', $missing );
		self::assertNotContains( 'wp_users', $missing );
		self::assertContains( 'wp_posts', $missing );
	}
}
