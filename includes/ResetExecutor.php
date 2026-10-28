<?php

namespace EsmondMcCain\NuclearReset;

final class ResetExecutor {
	/**
	 * Executes a confirmed single-site reset from the WP-CLI command only.
	 *
	 * @param string $site_title    New site title.
	 * @param string $admin_user    New administrator username.
	 * @param string $admin_email   New administrator email.
	 * @param string $admin_password New administrator password.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function execute( $site_title, $admin_user, $admin_email, $admin_password ) {
		$preflight     = Preflight::collect();
		$filesystem    = FilesystemPreflight::collect();
		$operation_id  = wp_generate_uuid4();
		$blocked_paths = self::get_blocked_paths( $filesystem );

		if ( ! empty( $blocked_paths ) ) {
			return new \WP_Error( 'em_nuclear_reset_blocked_path', 'The preflight contains blocked filesystem paths.', $blocked_paths );
		}

		if ( ! OperationLock::acquire( $operation_id ) ) {
			return new \WP_Error( 'em_nuclear_reset_lock', 'Another reset operation is already in progress.' );
		}

		try {
			self::append_log( $operation_id, $preflight['site_url'], 'started', array( 'table_count' => count( $preflight['database_tables'] ) ) );
			self::delete_eligible_paths( $filesystem );
			self::append_log( $operation_id, $preflight['site_url'], 'filesystem_complete', array() );
			self::drop_prefixed_tables( $preflight['database_tables'], $preflight['database_prefix'] );
			self::append_log( $operation_id, $preflight['site_url'], 'database_dropped', array() );

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$installation = wp_install( $site_title, $admin_user, $admin_email, true, '', $admin_password );
			switch_theme( $preflight['active_theme'] );
			$activation = activate_plugin( $preflight['plugin_basename'], '', false, true );

			if ( is_wp_error( $activation ) ) {
				throw new \RuntimeException( $activation->get_error_message() );
			}

			$verification = self::verify( $preflight['active_theme'], $preflight['plugin_basename'], $installation['user_id'] );

			if ( ! $verification['is_valid'] ) {
				throw new \RuntimeException( implode( ' ', $verification['errors'] ) );
			}

			self::append_log( $operation_id, $preflight['site_url'], 'complete', $verification );

			return array(
				'operation_id'  => $operation_id,
				'admin_user_id' => $installation['user_id'],
				'verification'  => $verification,
			);
		} catch ( \Throwable $error ) {
			self::append_log( $operation_id, $preflight['site_url'], 'failed', array( 'message' => $error->getMessage() ) );

			return new \WP_Error( 'em_nuclear_reset_failed', $error->getMessage() );
		} finally {
			OperationLock::release();
		}
	}

	/**
	 * Removes only preflight-approved eligible paths.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $filesystem Filesystem preflight result.
	 * @return void
	 */
	private static function delete_eligible_paths( array $filesystem ) {
		foreach ( $filesystem as $group ) {
			foreach ( $group as $path ) {
				if ( 'eligible' !== $path['status'] ) {
					continue;
				}

				self::delete_path( $path['path'] );
			}
		}
	}

	/**
	 * Rejects symlinks, then recursively deletes a regular file or directory.
	 *
	 * @param string $path Approved eligible path.
	 * @return void
	 */
	private static function delete_path( $path ) {
		self::assert_no_symlinks( $path );

		require_once ABSPATH . 'wp-admin/includes/file.php';
		global $wp_filesystem;

		if ( ! WP_Filesystem() || ! $wp_filesystem->delete( $path, true, 'd' ) ) {
			throw new \RuntimeException( 'Unable to remove eligible path: ' . esc_html( $path ) );
		}
	}

	/**
	 * Prevents filesystem deletion if any path is a symlink.
	 *
	 * @param string $path Approved eligible path.
	 * @return void
	 */
	private static function assert_no_symlinks( $path ) {
		if ( is_link( $path ) ) {
			throw new \RuntimeException( 'Refusing to delete symlinked path: ' . esc_html( $path ) );
		}

		if ( ! is_dir( $path ) ) {
			return;
		}

		$directory = new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS );
		$iterator  = new \RecursiveIteratorIterator( $directory );

		foreach ( $iterator as $item ) {
			if ( $item->isLink() ) {
				throw new \RuntimeException( 'Refusing to delete directory containing a symlink: ' . esc_html( $item->getPathname() ) );
			}
		}
	}

	/**
	 * Drops only approved current-prefix table names.
	 *
	 * @param string[] $tables Approved database table names.
	 * @param string   $prefix Current WordPress database prefix.
	 * @return void
	 */
	private static function drop_prefixed_tables( array $tables, $prefix ) {
		global $wpdb;

		foreach ( $tables as $table ) {
			if ( 0 !== strpos( $table, $prefix ) || ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
				throw new \RuntimeException( 'Refusing an invalid database table target: ' . sanitize_key( $table ) );
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Validated table identifiers cannot be parameterized by wpdb.
			if ( false === $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ) ) {
				throw new \RuntimeException( 'Unable to drop table: ' . sanitize_key( $table ) );
			}
		}
	}

	/**
	 * Verifies the recovered WordPress installation.
	 *
	 * @param string $active_theme Active stylesheet before reset.
	 * @param string $plugin_file  Preserved plugin basename.
	 * @param int    $admin_user_id Recreated administrator user ID.
	 * @return array<string, mixed>
	 */
	private static function verify( $active_theme, $plugin_file, $admin_user_id ): array {
		$errors    = array();
		$preflight = Preflight::collect();

		if ( ! empty( $preflight['missing_core_tables'] ) ) {
			$errors[] = 'Core tables are missing after schema recreation.';
		}

		if ( get_stylesheet() !== $active_theme ) {
			$errors[] = 'The preserved active theme was not restored.';
		}

		if ( ! is_plugin_active( $plugin_file ) ) {
			$errors[] = 'The reset plugin was not reactivated.';
		}

		if ( ! user_can( $admin_user_id, 'manage_options' ) ) {
			$errors[] = 'The recreated administrator cannot manage options.';
		}

		if ( ! self::has_required_auto_increment_columns() ) {
			$errors[] = 'One or more required core auto-increment columns are missing.';
		}

		if ( ! wp_is_writable( WP_PLUGIN_DIR ) ) {
			$errors[] = 'The plugins directory is not writable for migration-plugin installation.';
		}

		return array(
			'is_valid' => empty( $errors ),
			'errors'   => $errors,
		);
	}

	/**
	 * Checks auto-increment attributes needed by a restored core schema.
	 *
	 * @return bool
	 */
	private static function has_required_auto_increment_columns(): bool {
		global $wpdb;

		$columns = array(
			$wpdb->commentmeta => 'meta_id',
			$wpdb->comments    => 'comment_ID',
			$wpdb->links       => 'link_id',
			$wpdb->options     => 'option_id',
			$wpdb->postmeta    => 'meta_id',
			$wpdb->posts       => 'ID',
			$wpdb->termmeta    => 'meta_id',
			$wpdb->terms       => 'term_id',
			$wpdb->usermeta    => 'umeta_id',
			$wpdb->users       => 'ID',
		);

		foreach ( $columns as $table => $column ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- WordPress table property is a trusted identifier.
			$query = $wpdb->prepare( "SHOW COLUMNS FROM `{$table}` WHERE Field = %s", $column );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The query was prepared immediately above.
			$definition = $wpdb->get_row( $query, ARRAY_A );

			if ( empty( $definition['Extra'] ) || false === strpos( $definition['Extra'], 'auto_increment' ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Collects paths blocked by the current preflight.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $filesystem Filesystem preflight result.
	 * @return string[]
	 */
	private static function get_blocked_paths( array $filesystem ): array {
		$blocked_paths = array();

		foreach ( $filesystem as $group ) {
			foreach ( $group as $path ) {
				if ( 'blocked' === $path['status'] ) {
					$blocked_paths[] = $path['path'];
				}
			}
		}

		return $blocked_paths;
	}

	/**
	 * Appends a timestamped operation record without exposing credentials.
	 *
	 * @param string               $operation_id Operation identifier.
	 * @param string               $site_url     Current site URL.
	 * @param string               $phase        Current phase.
	 * @param array<string, mixed> $details      Non-secret phase details.
	 * @return void
	 */
	private static function append_log( $operation_id, $site_url, $phase, array $details ) {
		$record = OperationLog::build_record( $operation_id, $site_url, $phase, $details, gmdate( 'c' ) );

		if ( ! OperationLog::append( $record ) ) {
			throw new \RuntimeException( 'Unable to append the operation log.' );
		}
	}
}
