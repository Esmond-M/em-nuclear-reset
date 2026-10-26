<?php

namespace EsmondMcCain\NuclearReset;

final class AdminPage {
	private const CAPABILITY = 'manage_options';
	private const MENU_SLUG  = 'em-nuclear-reset';

	/**
	 * Registers the read-only preflight page.
	 *
	 * @return void
	 */
	public static function register() {
		add_management_page(
			__( 'EM Nuclear Reset', 'em-nuclear-reset' ),
			__( 'EM Nuclear Reset', 'em-nuclear-reset' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * Renders the preflight facts without making any changes.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'em-nuclear-reset' ) );
		}

		$preflight  = Preflight::collect();
		$filesystem = FilesystemPreflight::collect();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EM Nuclear Reset', 'em-nuclear-reset' ); ?></h1>
			<p><?php esc_html_e( 'Read-only preflight. No site data or files have been changed.', 'em-nuclear-reset' ); ?></p>

			<?php if ( $preflight['is_multisite'] ) : ?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Multisite is not supported. Reset actions are unavailable for this installation.', 'em-nuclear-reset' ); ?></p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Installation', 'em-nuclear-reset' ); ?></h2>
			<table class="widefat striped">
				<tbody>
					<?php self::render_row( __( 'Site URL', 'em-nuclear-reset' ), $preflight['site_url'] ); ?>
					<?php self::render_row( __( 'Home URL', 'em-nuclear-reset' ), $preflight['home_url'] ); ?>
					<?php self::render_row( __( 'Database prefix', 'em-nuclear-reset' ), $preflight['database_prefix'] ); ?>
					<?php self::render_row( __( 'Preserved plugin', 'em-nuclear-reset' ), $preflight['plugin_basename'] ); ?>
					<?php self::render_row( __( 'Active theme', 'em-nuclear-reset' ), $preflight['active_theme'] ); ?>
					<?php self::render_row( __( 'Parent theme', 'em-nuclear-reset' ), $preflight['active_parent_theme'] ); ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Scoped Database Tables', 'em-nuclear-reset' ); ?></h2>
			<p><?php esc_html_e( 'Only tables beginning with the displayed database prefix are listed. No tables have been selected for deletion.', 'em-nuclear-reset' ); ?></p>
			<?php self::render_table_list( $preflight['database_tables'] ); ?>

			<h2><?php esc_html_e( 'Core Schema Check', 'em-nuclear-reset' ); ?></h2>
			<?php if ( empty( $preflight['missing_core_tables'] ) ) : ?>
				<p class="notice notice-success inline"><span><?php esc_html_e( 'All expected WordPress core tables are present.', 'em-nuclear-reset' ); ?></span></p>
			<?php else : ?>
				<p class="notice notice-warning inline"><span><?php esc_html_e( 'Some expected WordPress core tables are missing.', 'em-nuclear-reset' ); ?></span></p>
				<?php self::render_table_list( $preflight['missing_core_tables'] ); ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Filesystem Preview', 'em-nuclear-reset' ); ?></h2>
			<p><?php esc_html_e( 'Entries are listed without recursion. Preserved entries are retained, eligible entries are potential future cleanup targets, and blocked entries require manual review.', 'em-nuclear-reset' ); ?></p>
			<?php self::render_path_group( __( 'Uploads', 'em-nuclear-reset' ), $filesystem['uploads'] ); ?>
			<?php self::render_path_group( __( 'Plugins', 'em-nuclear-reset' ), $filesystem['plugins'] ); ?>
			<?php self::render_path_group( __( 'Themes', 'em-nuclear-reset' ), $filesystem['themes'] ); ?>
		</div>
		<?php
	}

	/**
	 * Renders one installation fact.
	 *
	 * @param string $label Row label.
	 * @param string $value Row value.
	 * @return void
	 */
	private static function render_row( $label, $value ) {
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td><code><?php echo esc_html( $value ); ?></code></td>
		</tr>
		<?php
	}

	/**
	 * Renders a list of table names.
	 *
	 * @param string[] $tables Table names.
	 * @return void
	 */
	private static function render_table_list( array $tables ) {
		if ( empty( $tables ) ) {
			echo '<p>' . esc_html__( 'No matching tables were found.', 'em-nuclear-reset' ) . '</p>';
			return;
		}
		?>
		<ul>
			<?php foreach ( $tables as $table ) : ?>
				<li><code><?php echo esc_html( $table ); ?></code></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	/**
	 * Renders one filesystem classification group.
	 *
	 * @param string                       $heading Group heading.
	 * @param array<int, array<string, string>> $paths   Classified paths.
	 * @return void
	 */
	private static function render_path_group( $heading, array $paths ) {
		?>
		<h3><?php echo esc_html( $heading ); ?></h3>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Path', 'em-nuclear-reset' ); ?></th>
					<th><?php esc_html_e( 'Classification', 'em-nuclear-reset' ); ?></th>
					<th><?php esc_html_e( 'Review note', 'em-nuclear-reset' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $paths as $path ) : ?>
					<tr>
						<td><code><?php echo esc_html( $path['path'] ); ?></code></td>
						<td><?php echo esc_html( $path['status'] ); ?></td>
						<td><?php echo esc_html( $path['reason'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
