<?php

namespace EsmondMcCain\NuclearReset;

final class ResetCoordinator {
	/**
	 * Validates the conditions required before any reset execution may begin.
	 *
	 * This method is intentionally read-only. A future executor must acquire the
	 * operation lock and append an operation record only after this check passes.
	 *
	 * @param string $typed_phrase  Entered confirmation phrase.
	 * @param string $site_url      Current site URL.
	 * @param bool   $acknowledged  Whether irreversible action was acknowledged.
	 * @param bool   $is_multisite  Whether the installation is multisite.
	 * @param bool   $can_manage    Whether the current user can manage options.
	 * @param bool   $nonce_verified Whether the reset nonce is valid.
	 * @param bool   $lock_is_held  Whether another reset holds the operation lock.
	 * @return array<string, mixed>
	 */
	public static function get_readiness( $typed_phrase, $site_url, $acknowledged, $is_multisite, $can_manage, $nonce_verified, $lock_is_held ): array {
		$blockers = array();

		if ( $is_multisite ) {
			$blockers[] = 'Multisite installations are not supported.';
		}

		if ( ! $can_manage ) {
			$blockers[] = 'The current user does not have permission to reset this site.';
		}

		if ( ! $nonce_verified ) {
			$blockers[] = 'The reset request could not be verified.';
		}

		if ( ! Confirmation::is_valid( $typed_phrase, $site_url, $acknowledged ) ) {
			$blockers[] = 'The typed confirmation or irreversible-action acknowledgement is invalid.';
		}

		if ( $lock_is_held ) {
			$blockers[] = 'Another reset operation is already in progress.';
		}

		return array(
			'is_ready' => empty( $blockers ),
			'blockers' => $blockers,
		);
	}

	/**
	 * Validates reset conditions available to the WP-CLI execution surface.
	 *
	 * Shell access is the authorization boundary for this command, so browser
	 * capability and nonce checks do not apply here.
	 *
	 * @param string $typed_phrase Entered confirmation phrase.
	 * @param string $site_url     Current site URL.
	 * @param bool   $acknowledged Whether irreversible action was acknowledged.
	 * @param bool   $is_multisite Whether the installation is multisite.
	 * @param bool   $lock_is_held Whether another reset holds the operation lock.
	 * @return array<string, mixed>
	 */
	public static function get_cli_readiness( $typed_phrase, $site_url, $acknowledged, $is_multisite, $lock_is_held ): array {
		return self::get_readiness(
			$typed_phrase,
			$site_url,
			$acknowledged,
			$is_multisite,
			true,
			true,
			$lock_is_held
		);
	}

	/**
	 * Builds the immutable scope to record before a future reset execution.
	 *
	 * @param string                         $operation_id     Unique operation identifier.
	 * @param string                         $site_url         Current site URL.
	 * @param string[]                       $database_tables  Approved prefixed table names.
	 * @param array<string, array<int, mixed>> $filesystem_scope Previewed filesystem scope.
	 * @return array<string, mixed>
	 */
	public static function build_operation_plan( $operation_id, $site_url, array $database_tables, array $filesystem_scope ): array {
		return array(
			'operation_id'      => $operation_id,
			'site_url'          => $site_url,
			'database_tables'   => $database_tables,
			'filesystem_scope'  => $filesystem_scope,
			'execution_enabled' => false,
		);
	}
}
