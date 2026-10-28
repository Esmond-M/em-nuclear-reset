<?php

namespace EsmondMcCain\NuclearReset;

use WP_CLI;

final class CliCommand {
	/**
	 * Displays the current reset scope without making any changes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp em-nuclear-reset plan
	 *
	 * @return void
	 */
	public function plan() {
		$preflight    = Preflight::collect();
		$filesystem   = FilesystemPreflight::collect();
		$operation_id = wp_generate_uuid4();
		$plan         = ResetCoordinator::build_operation_plan(
			$operation_id,
			$preflight['site_url'],
			$preflight['database_tables'],
			$filesystem
		);

		WP_CLI::log( wp_json_encode( $plan, JSON_PRETTY_PRINT ) );
		WP_CLI::success( 'Dry-run plan generated. No files or database tables were changed.' );
	}

	/**
	 * Executes a reset after command-specific safeguards have passed.
	 *
	 * ## OPTIONS
	 *
	 * --execute=<true>
	 * : Required execution switch.
	 *
	 * --confirm=<phrase>
	 * : Exact phrase shown by the preflight page.
	 *
	 * --acknowledge=<true>
	 * : Acknowledges the irreversible action.
	 *
	 * --site-title=<title>
	 * : Site title for the rebuilt WordPress installation.
	 *
	 * --admin-user=<username>
	 * : Username for the rebuilt administrator.
	 *
	 * --admin-email=<email>
	 * : Email for the rebuilt administrator.
	 *
	 * --admin-password-env=<variable>
	 * : Environment variable containing the password for the rebuilt administrator.
	 *
	 * [--yes]
	 * : Confirms the irreversible reset after all other safeguards pass.
	 *
	 * ## EXAMPLES
	 *
	 *     wp em-nuclear-reset execute --execute=true --confirm='RESET https://site.test' --acknowledge=true --site-title='Clean Site' --admin-user=admin --admin-email=admin@example.test --admin-password-env=EM_NUCLEAR_RESET_ADMIN_PASSWORD --yes
	 *
	 * @param string[]               $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 * @return void
	 */
	public function execute( $args, $assoc_args ) {
		$required_arguments = array( 'execute', 'confirm', 'acknowledge', 'site-title', 'admin-user', 'admin-email', 'admin-password-env' );

		foreach ( $required_arguments as $argument ) {
			if ( empty( $assoc_args[ $argument ] ) ) {
				WP_CLI::error( sprintf( 'Missing required --%s argument.', $argument ) );
			}
		}

		if ( '1' !== (string) $assoc_args['execute'] && 'true' !== (string) $assoc_args['execute'] ) {
			WP_CLI::error( 'The --execute argument must be set to true.' );
		}

		$readiness = ResetCoordinator::get_cli_readiness(
			$assoc_args['confirm'],
			site_url(),
			true,
			is_multisite(),
			OperationLock::is_held()
		);

		if ( ! $readiness['is_ready'] ) {
			WP_CLI::error( implode( ' ', $readiness['blockers'] ) );
		}

		WP_CLI::confirm( 'This permanently resets the current WordPress installation.', $assoc_args );
		$admin_password = self::get_admin_password( $assoc_args['admin-password-env'] );
		$result         = ResetExecutor::execute(
			sanitize_text_field( $assoc_args['site-title'] ),
			sanitize_user( $assoc_args['admin-user'], true ),
			sanitize_email( $assoc_args['admin-email'] ),
			$admin_password
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( sprintf( 'Reset completed. New administrator user ID: %d.', $result['admin_user_id'] ) );
	}

	/**
	 * Retrieves the administrator password without exposing it in process arguments.
	 *
	 * @param string $environment_variable Environment variable name.
	 * @return string
	 */
	private static function get_admin_password( $environment_variable ) {
		if ( ! preg_match( '/^[A-Z_][A-Z0-9_]*$/', $environment_variable ) ) {
			WP_CLI::error( 'The --admin-password-env argument must be an uppercase environment variable name.' );
		}

		$password = getenv( $environment_variable );

		if ( false === $password || '' === $password ) {
			WP_CLI::error( sprintf( 'The %s environment variable is not set.', $environment_variable ) );
		}

		return $password;
	}
}
