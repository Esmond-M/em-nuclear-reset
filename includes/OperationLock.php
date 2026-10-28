<?php

namespace EsmondMcCain\NuclearReset;

final class OperationLock {
	private const FILE_NAME = 'active-operation.lock';

	/**
	 * Acquires the exclusive operation lock.
	 *
	 * @param string $operation_id Unique operation identifier.
	 * @return bool
	 */
	public static function acquire( $operation_id ): bool {
		if ( ! OperationLog::ensure_directory() ) {
			return false;
		}

		$lock_path = self::get_path();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Exclusive creation prevents concurrent destructive operations.
		$lock = fopen( $lock_path, 'x' );

		if ( false === $lock ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- The lock stores only the non-secret operation identifier.
		fwrite( $lock, $operation_id );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes the exclusive lock handle after writing the operation identifier.
		fclose( $lock );

		return true;
	}

	/**
	 * Releases the exclusive operation lock.
	 *
	 * @return bool
	 */
	public static function release(): bool {
		$lock_path = self::get_path();

		if ( ! file_exists( $lock_path ) ) {
			return true;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Removes only the lock file in the preserved log directory.
		return unlink( $lock_path );
	}

	/**
	 * Checks whether a reset operation already holds the lock.
	 *
	 * @return bool
	 */
	public static function is_held(): bool {
		return file_exists( self::get_path() );
	}

	/**
	 * Returns the path to the exclusive lock file.
	 *
	 * @return string
	 */
	private static function get_path(): string {
		return OperationLog::get_directory() . '/' . self::FILE_NAME;
	}
}
