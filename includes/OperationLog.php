<?php

namespace EsmondMcCain\NuclearReset;

final class OperationLog {
	/**
	 * Builds the durable record schema for a future reset operation.
	 *
	 * This does not write a file. The reset coordinator will write records only
	 * after its confirmation and lock checks have succeeded.
	 *
	 * @param string               $operation_id Unique operation identifier.
	 * @param string               $site_url     Site URL at operation start.
	 * @param string               $phase        Current operation phase.
	 * @param array<string, mixed> $details      Non-secret operation details.
	 * @param string               $recorded_at  ISO 8601 timestamp.
	 * @return array<string, mixed>
	 */
	public static function build_record( $operation_id, $site_url, $phase, array $details, $recorded_at ): array {
		return array(
			'schema_version' => 1,
			'operation_id'   => $operation_id,
			'site_url'       => $site_url,
			'phase'          => $phase,
			'recorded_at'    => $recorded_at,
			'details'        => $details,
		);
	}

	/**
	 * Returns the preserved filesystem location for future JSON operation logs.
	 *
	 * @return string
	 */
	public static function get_directory(): string {
		return WP_CONTENT_DIR . '/em-nuclear-reset-logs';
	}
}
