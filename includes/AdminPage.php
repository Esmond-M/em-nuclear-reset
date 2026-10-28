<?php

namespace EsmondMcCain\NuclearReset;

final class AdminPage {
	private const CAPABILITY = 'manage_options';
	private const MENU_SLUG  = 'em-nuclear-reset';
	private const ACTION     = 'em_nuclear_reset_execute';

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
	 * Handles a confirmed reset from the Tools page.
	 *
	 * @return void
	 */
	public static function handle_execute() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to reset this site.', 'em-nuclear-reset' ) );
		}

		$preflight      = Preflight::collect();
		$current_user   = wp_get_current_user();
		$typed_phrase   = isset( $_POST['em_nuclear_reset_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['em_nuclear_reset_confirmation'] ) ) : '';
		$acknowledged   = isset( $_POST['em_nuclear_reset_acknowledged'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['em_nuclear_reset_acknowledged'] ) );
		$nonce_verified = isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::ACTION );
		$readiness      = ResetCoordinator::get_readiness(
			$typed_phrase,
			$preflight['site_url'],
			$acknowledged,
			$preflight['is_multisite'],
			true,
			$nonce_verified,
			OperationLock::is_held()
		);

		if ( ! $readiness['is_ready'] ) {
			wp_die( esc_html( implode( ' ', $readiness['blockers'] ) ) );
		}

		$result = ResetExecutor::execute(
			get_option( 'blogname' ),
			$current_user->user_login,
			$current_user->user_email,
			wp_generate_password( 64, true, true ),
			$current_user->user_pass
		);

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $result['admin_user_id'] );
		wp_set_auth_cookie( $result['admin_user_id'] );

		wp_safe_redirect( admin_url( 'tools.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	/**
	 * Renders reset preflight facts and the confirmed execution form.
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'em-nuclear-reset' ) );
		}

		$preflight           = Preflight::collect();
		$filesystem          = FilesystemPreflight::collect();
		$confirmation_phrase = Confirmation::get_required_phrase( $preflight['site_url'] );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EM Nuclear Reset', 'em-nuclear-reset' ); ?></h1>
			<p><?php esc_html_e( 'Review the reset scope before executing this irreversible operation.', 'em-nuclear-reset' ); ?></p>

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
			<p><?php esc_html_e( 'Preserved entries are retained, eligible entries are potential future cleanup targets, and blocked entries require manual review. Counts and sizes skip symlinks and stop after the safe scan limit.', 'em-nuclear-reset' ); ?></p>
			<?php self::render_path_group( __( 'Uploads', 'em-nuclear-reset' ), $filesystem['uploads'] ); ?>
			<?php self::render_path_group( __( 'Plugins', 'em-nuclear-reset' ), $filesystem['plugins'] ); ?>
			<?php self::render_path_group( __( 'Themes', 'em-nuclear-reset' ), $filesystem['themes'] ); ?>

			<?php if ( ! $preflight['is_multisite'] ) : ?>
				<h2><?php esc_html_e( 'Reset Site', 'em-nuclear-reset' ); ?></h2>
				<p><?php esc_html_e( 'This permanently deletes the scoped site data and eligible files, then rebuilds WordPress. The current administrator account is restored with its existing password.', 'em-nuclear-reset' ); ?></p>
				<p><?php esc_html_e( 'Operation logs are written outside cleanup scope in wp-content/em-nuclear-reset-logs.', 'em-nuclear-reset' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
					<?php wp_nonce_field( self::ACTION ); ?>
					<p>
						<label for="em-nuclear-reset-confirmation">
							<?php esc_html_e( 'Type the following phrase to continue:', 'em-nuclear-reset' ); ?>
							<code><?php echo esc_html( $confirmation_phrase ); ?></code>
						</label><br>
						<input id="em-nuclear-reset-confirmation" name="em_nuclear_reset_confirmation" type="text" class="regular-text" autocomplete="off" required>
					</p>
					<p>
						<label>
							<input name="em_nuclear_reset_acknowledged" type="checkbox" value="1" required>
							<?php esc_html_e( 'I understand this operation is permanent and cannot be undone.', 'em-nuclear-reset' ); ?>
						</label>
					</p>
					<?php submit_button( __( 'Reset WordPress', 'em-nuclear-reset' ), 'delete', 'submit', false ); ?>
				</form>
			<?php endif; ?>
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
	 * @param string                           $heading Group heading.
	 * @param array<int, array<string, mixed>> $paths   Classified paths.
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
					<th><?php esc_html_e( 'Estimate', 'em-nuclear-reset' ); ?></th>
					<th><?php esc_html_e( 'Review note', 'em-nuclear-reset' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $paths as $path ) : ?>
					<tr>
						<td><code><?php echo esc_html( $path['path'] ); ?></code></td>
						<td><?php echo esc_html( $path['status'] ); ?></td>
						<td>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: file count, 2: formatted byte size. */
									_n( '%1$s file, %2$s', '%1$s files, %2$s', $path['file_count'], 'em-nuclear-reset' ),
									number_format_i18n( $path['file_count'] ),
									FilesystemPreflight::format_bytes( $path['size_bytes'] )
								)
							);
							if ( $path['truncated'] ) {
								echo ' ' . esc_html__( '(scan limit reached)', 'em-nuclear-reset' );
							}
							?>
						</td>
						<td><?php echo esc_html( $path['reason'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
