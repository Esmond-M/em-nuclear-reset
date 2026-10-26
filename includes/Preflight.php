<?php

namespace EsmondMcCain\NuclearReset;

final class Preflight {
	/**
	 * WordPress tables recreated by the core installation routine.
	 *
	 * @var string[]
	 */
	private const CORE_TABLE_SUFFIXES = array(
		'commentmeta',
		'comments',
		'links',
		'options',
		'postmeta',
		'posts',
		'term_relationships',
		'term_taxonomy',
		'termmeta',
		'terms',
		'usermeta',
		'users',
	);

	/**
	 * Returns the database facts needed to show a non-destructive reset preview.
	 *
	 * @return array<string, mixed>
	 */
	public static function collect(): array {
		global $wpdb;

		$prefix = (string) $wpdb->prefix;
		$tables = self::get_prefixed_tables( $wpdb, $prefix );

		return array(
			'database_prefix'     => $prefix,
			'database_tables'     => $tables,
			'missing_core_tables' => self::get_missing_core_tables( $tables, $prefix ),
			'is_multisite'        => is_multisite(),
			'plugin_basename'     => plugin_basename( EM_NUCLEAR_RESET_FILE ),
			'active_theme'        => get_stylesheet(),
			'active_parent_theme' => get_template(),
			'site_url'            => site_url(),
			'home_url'            => home_url(),
		);
	}

	/**
	 * Retains table names belonging exactly to one WordPress table prefix.
	 *
	 * @param string[] $tables Table names returned by the database server.
	 * @param string   $prefix Active WordPress table prefix.
	 * @return string[]
	 */
	public static function filter_prefixed_tables( array $tables, $prefix ): array {
		$prefixed_tables = array();

		foreach ( $tables as $table ) {
			if ( 0 === strpos( $table, $prefix ) ) {
				$prefixed_tables[] = $table;
			}
		}

		sort( $prefixed_tables, SORT_STRING );

		return $prefixed_tables;
	}

	/**
	 * Returns the expected core tables that are not present in the preview.
	 *
	 * @param string[] $tables Prefixed database tables.
	 * @param string   $prefix Active WordPress table prefix.
	 * @return string[]
	 */
	public static function get_missing_core_tables( array $tables, $prefix ): array {
		$missing_tables = array();

		foreach ( self::CORE_TABLE_SUFFIXES as $suffix ) {
			$table = $prefix . $suffix;

			if ( ! in_array( $table, $tables, true ) ) {
				$missing_tables[] = $table;
			}
		}

		return $missing_tables;
	}

	/**
	 * Retrieves only the current installation's candidate table names.
	 *
	 * @param \wpdb  $database WordPress database connection.
	 * @param string $prefix   Active WordPress table prefix.
	 * @return string[]
	 */
	private static function get_prefixed_tables( $database, $prefix ): array {
		$like   = $database->esc_like( $prefix ) . '%';
		$query  = $database->prepare( 'SHOW TABLES LIKE %s', $like );
		$tables = $database->get_col( $query );

		return self::filter_prefixed_tables( $tables, $prefix );
	}
}
