<?php

namespace EsmondMcCain\NuclearReset;

final class FilesystemPreflight {
	/**
	 * Collects the file paths relevant to a future reset preview.
	 *
	 * @return array<string, array<int, array<string, string>>>
	 */
	public static function collect(): array {
		$uploads           = wp_upload_dir();
		$plugin_root       = WP_PLUGIN_DIR;
		$theme_root        = get_theme_root();
		$preserved_plugins = array( EM_NUCLEAR_RESET_DIR );
		$preserved_themes  = array(
			get_stylesheet_directory(),
			get_template_directory(),
		);

		return array(
			'uploads' => array(
				self::inspect_path( $uploads['basedir'], WP_CONTENT_DIR, 'eligible' ),
			),
			'plugins' => self::inspect_directory_entries( $plugin_root, $preserved_plugins ),
			'themes'  => self::inspect_directory_entries( $theme_root, $preserved_themes ),
		);
	}

	/**
	 * Checks whether a resolved path is contained by its allowed root.
	 *
	 * @param string $path         Resolved candidate path.
	 * @param string $allowed_root Resolved allowed root.
	 * @return bool
	 */
	public static function is_path_within( $path, $allowed_root ): bool {
		$normalized_path = self::normalize_path( $path );
		$normalized_root = rtrim( self::normalize_path( $allowed_root ), '/' );

		return $normalized_path === $normalized_root || 0 === strpos( $normalized_path, $normalized_root . '/' );
	}

	/**
	 * Inspects the immediate entries within an approved root without recursing.
	 *
	 * @param string   $root            Approved root directory.
	 * @param string[] $preserved_paths Paths that must not be reset.
	 * @return array<int, array<string, string>>
	 */
	private static function inspect_directory_entries( $root, array $preserved_paths ): array {
		if ( ! is_dir( $root ) ) {
			return array(
				self::inspect_path( $root, $root, 'blocked' ),
			);
		}

		$entries     = scandir( $root );
		$inspections = array();

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$path          = $root . DIRECTORY_SEPARATOR . $entry;
			$status        = self::is_preserved_path( $path, $preserved_paths ) ? 'preserved' : 'eligible';
			$inspections[] = self::inspect_path( $path, $root, $status );
		}

		return $inspections;
	}

	/**
	 * Classifies one path without reading, changing, or following its contents.
	 *
	 * @param string $path         Candidate path.
	 * @param string $allowed_root Approved containing root.
	 * @param string $status       Initial classification.
	 * @return array<string, string>
	 */
	private static function inspect_path( $path, $allowed_root, $status ): array {
		$resolved_path = realpath( $path );
		$resolved_root = realpath( $allowed_root );
		$reason        = '';

		if ( is_link( $path ) ) {
			$status = 'blocked';
			$reason = 'Symlinked paths are not eligible for cleanup.';
		} elseif ( false === $resolved_path || false === $resolved_root ) {
			$status = 'blocked';
			$reason = 'The path or its allowed root could not be resolved.';
		} elseif ( ! self::is_path_within( $resolved_path, $resolved_root ) ) {
			$status = 'blocked';
			$reason = 'The resolved path is outside its approved root.';
		}

		return array(
			'path'   => self::normalize_path( $path ),
			'status' => $status,
			'reason' => $reason,
		);
	}

	/**
	 * Determines whether a candidate path is one of the preserved paths.
	 *
	 * @param string   $path            Candidate path.
	 * @param string[] $preserved_paths Paths that must be preserved.
	 * @return bool
	 */
	private static function is_preserved_path( $path, array $preserved_paths ): bool {
		foreach ( $preserved_paths as $preserved_path ) {
			if ( self::normalize_path( $path ) === self::normalize_path( $preserved_path ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalizes a path for display and containment comparison.
	 *
	 * @param string $path Filesystem path.
	 * @return string
	 */
	private static function normalize_path( $path ): string {
		return rtrim( str_replace( '\\', '/', $path ), '/' );
	}
}
