<?php

namespace EsmondMcCain\NuclearReset;

final class Confirmation {
	/**
	 * Returns the site-specific phrase required before reset execution.
	 *
	 * @param string $site_url Current site URL.
	 * @return string
	 */
	public static function get_required_phrase( $site_url ): string {
		return 'RESET ' . rtrim( $site_url, '/' );
	}

	/**
	 * Validates the typed confirmation and irreversible-action acknowledgement.
	 *
	 * @param string $typed_phrase Entered confirmation phrase.
	 * @param string $site_url     Current site URL.
	 * @param bool   $acknowledged Whether irreversible action was acknowledged.
	 * @return bool
	 */
	public static function is_valid( $typed_phrase, $site_url, $acknowledged ): bool {
		return $acknowledged && hash_equals( self::get_required_phrase( $site_url ), trim( $typed_phrase ) );
	}
}
