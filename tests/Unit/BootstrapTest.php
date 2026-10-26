<?php

namespace EsmondMcCain\NuclearReset\Tests\Unit;

use PHPUnit\Framework\TestCase;
use EsmondMcCain\NuclearReset\Confirmation;
use EsmondMcCain\NuclearReset\FilesystemPreflight;
use EsmondMcCain\NuclearReset\OperationLog;
use EsmondMcCain\NuclearReset\Preflight;
use EsmondMcCain\NuclearReset\ResetCoordinator;

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

	public function test_filesystem_preflight_rejects_sibling_paths(): void {
		self::assertTrue(
			FilesystemPreflight::is_path_within(
				'C:\\site\\wp-content\\plugins\\em-nuclear-reset',
				'C:\\site\\wp-content\\plugins'
			)
		);
		self::assertFalse(
			FilesystemPreflight::is_path_within(
				'C:\\site\\wp-content\\plugins-old\\other-plugin',
				'C:\\site\\wp-content\\plugins'
			)
		);
	}

	public function test_filesystem_preflight_formats_estimated_sizes(): void {
		self::assertSame( '512 B', FilesystemPreflight::format_bytes( 512 ) );
		self::assertSame( '1.5 KB', FilesystemPreflight::format_bytes( 1536 ) );
	}

	public function test_confirmation_requires_the_current_site_url_and_acknowledgement(): void {
		$phrase = Confirmation::get_required_phrase( 'https://site.test/example/' );

		self::assertSame( 'RESET https://site.test/example', $phrase );
		self::assertTrue( Confirmation::is_valid( $phrase, 'https://site.test/example/', true ) );
		self::assertFalse( Confirmation::is_valid( $phrase, 'https://other.test', true ) );
		self::assertFalse( Confirmation::is_valid( $phrase, 'https://site.test/example/', false ) );
	}

	public function test_operation_log_record_is_structured_and_non_secret(): void {
		$record = OperationLog::build_record(
			'example-operation',
			'https://site.test',
			'preflight',
			array( 'table_count' => 12 ),
			'2026-10-26T00:00:00+00:00'
		);

		self::assertSame( 1, $record['schema_version'] );
		self::assertSame( 'example-operation', $record['operation_id'] );
		self::assertSame( 12, $record['details']['table_count'] );
	}

	public function test_reset_coordinator_blocks_unverified_or_concurrent_resets(): void {
		$readiness = ResetCoordinator::get_readiness(
			'RESET https://site.test',
			'https://site.test',
			true,
			false,
			true,
			false,
			true
		);

		self::assertFalse( $readiness['is_ready'] );
		self::assertContains( 'The reset request could not be verified.', $readiness['blockers'] );
		self::assertContains( 'Another reset operation is already in progress.', $readiness['blockers'] );
	}

	public function test_reset_coordinator_builds_a_non_executing_plan(): void {
		$readiness = ResetCoordinator::get_readiness(
			'RESET https://site.test',
			'https://site.test',
			true,
			false,
			true,
			true,
			false
		);
		$plan      = ResetCoordinator::build_operation_plan(
			'example-operation',
			'https://site.test',
			array( 'wp_options', 'wp_posts' ),
			array( 'plugins' => array() )
		);

		self::assertTrue( $readiness['is_ready'] );
		self::assertSame( 'example-operation', $plan['operation_id'] );
		self::assertFalse( $plan['execution_enabled'] );
	}
}
