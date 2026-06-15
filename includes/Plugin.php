<?php
/**
 * Core plugin class.
 *
 * @package OnKuponAutonomousAgent
 */

namespace OnKupon\AutonomousAgent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin bootstrap.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Returns singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Runs plugin hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_post_onkupon_agent_set_status', [ $this, 'handle_status_action' ] );
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		update_option( 'onkupon_agent_status', 'paused', false );
	}

	/**
	 * Registers admin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'onkupon_agent_settings',
			'onkupon_agent_settings',
			[
				'type'              => 'array',
				'sanitize_callback' => [ $this, 'sanitize_settings' ],
				'default'           => [],
			]
		);
	}

	/**
	 * Sanitizes settings.
	 *
	 * @param array $settings Raw settings.
	 *
	 * @return array
	 */
	public function sanitize_settings( array $settings ): array {
		return [
			'daily_article_limit' => isset( $settings['daily_article_limit'] ) ? max( 0, absint( $settings['daily_article_limit'] ) ) : 3,
			'content_language'    => isset( $settings['content_language'] ) ? sanitize_text_field( $settings['content_language'] ) : 'tr',
			'min_quality_score'   => isset( $settings['min_quality_score'] ) ? floatval( $settings['min_quality_score'] ) : 0.70,
		];
	}

	/**
	 * Registers admin menu.
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		add_menu_page(
			__( 'OnKupon Agent', 'onkupon-autonomous-commerce-agent' ),
			__( 'OnKupon Agent', 'onkupon-autonomous-commerce-agent' ),
			'manage_options',
			'onkupon-agent',
			[ $this, 'render_dashboard' ],
			'dashicons-chart-area',
			56
		);

		add_submenu_page(
			'onkupon-agent',
			__( 'Settings', 'onkupon-autonomous-commerce-agent' ),
			__( 'Settings', 'onkupon-autonomous-commerce-agent' ),
			'manage_options',
			'onkupon-agent-settings',
			[ $this, 'render_settings' ]
		);
	}

	/**
	 * Handles start/pause/resume/stop actions.
	 *
	 * @return void
	 */
	public function handle_status_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'onkupon-autonomous-commerce-agent' ) );
		}

		check_admin_referer( 'onkupon_agent_set_status' );

		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'paused';

		$allowed = [ 'running', 'paused', 'stopped', 'emergency_stopped' ];

		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'paused';
		}

		update_option( 'onkupon_agent_status', $status, false );

		$this->log( 'info', 'control', 'Agent status changed.', [ 'status' => $status ] );

		wp_safe_redirect( admin_url( 'admin.php?page=onkupon-agent' ) );
		exit;
	}

	/**
	 * Renders dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = get_option( 'onkupon_agent_status', 'paused' );
		?>

```
	<div class="wrap">
		<h1><?php echo esc_html__( 'OnKupon Autonomous Commerce Agent', 'onkupon-autonomous-commerce-agent' ); ?></h1>

		<p>
			<strong><?php echo esc_html__( 'Status:', 'onkupon-autonomous-commerce-agent' ); ?></strong>
			<?php echo esc_html( $status ); ?>
		</p>

		<h2><?php echo esc_html__( 'Control Center', 'onkupon-autonomous-commerce-agent' ); ?></h2>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex; gap:8px; flex-wrap:wrap;">
			<input type="hidden" name="action" value="onkupon_agent_set_status" />
			<?php wp_nonce_field( 'onkupon_agent_set_status' ); ?>

			<button class="button button-primary" name="status" value="running">
				<?php echo esc_html__( 'Start / Resume', 'onkupon-autonomous-commerce-agent' ); ?>
			</button>

			<button class="button" name="status" value="paused">
				<?php echo esc_html__( 'Pause', 'onkupon-autonomous-commerce-agent' ); ?>
			</button>

			<button class="button" name="status" value="stopped">
				<?php echo esc_html__( 'Stop', 'onkupon-autonomous-commerce-agent' ); ?>
			</button>

			<button class="button button-secondary" name="status" value="emergency_stopped">
				<?php echo esc_html__( 'Emergency Stop', 'onkupon-autonomous-commerce-agent' ); ?>
			</button>
		</form>

		<h2><?php echo esc_html__( 'Next Build Steps', 'onkupon-autonomous-commerce-agent' ); ?></h2>
		<ol>
			<li><?php echo esc_html__( 'Run Codex with CODEX_PROMPT.md.', 'onkupon-autonomous-commerce-agent' ); ?></li>
			<li><?php echo esc_html__( 'Implement Action Scheduler jobs.', 'onkupon-autonomous-commerce-agent' ); ?></li>
			<li><?php echo esc_html__( 'Implement WooCommerce product scanner.', 'onkupon-autonomous-commerce-agent' ); ?></li>
			<li><?php echo esc_html__( 'Implement AI provider and content validator.', 'onkupon-autonomous-commerce-agent' ); ?></li>
			<li><?php echo esc_html__( 'Implement social queue and analytics dashboard.', 'onkupon-autonomous-commerce-agent' ); ?></li>
		</ol>
	</div>
	<?php
}

/**
 * Renders settings page.
 *
 * @return void
 */
public function render_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = get_option( 'onkupon_agent_settings', [] );

	$daily_article_limit = isset( $settings['daily_article_limit'] ) ? absint( $settings['daily_article_limit'] ) : 3;
	$content_language    = isset( $settings['content_language'] ) ? sanitize_text_field( $settings['content_language'] ) : 'tr';
	$min_quality_score   = isset( $settings['min_quality_score'] ) ? floatval( $settings['min_quality_score'] ) : 0.70;
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'OnKupon Agent Settings', 'onkupon-autonomous-commerce-agent' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'onkupon_agent_settings' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="daily_article_limit"><?php echo esc_html__( 'Daily Article Limit', 'onkupon-autonomous-commerce-agent' ); ?></label>
					</th>
					<td>
						<input type="number" min="0" max="50" id="daily_article_limit" name="onkupon_agent_settings[daily_article_limit]" value="<?php echo esc_attr( $daily_article_limit ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="content_language"><?php echo esc_html__( 'Content Language', 'onkupon-autonomous-commerce-agent' ); ?></label>
					</th>
					<td>
						<input type="text" id="content_language" name="onkupon_agent_settings[content_language]" value="<?php echo esc_attr( $content_language ); ?>" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="min_quality_score"><?php echo esc_html__( 'Minimum Quality Score', 'onkupon-autonomous-commerce-agent' ); ?></label>
					</th>
					<td>
						<input type="number" step="0.01" min="0" max="1" id="min_quality_score" name="onkupon_agent_settings[min_quality_score]" value="<?php echo esc_attr( $min_quality_score ); ?>" />
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Writes basic log entry.
 *
 * @param string $level Level.
 * @param string $channel Channel.
 * @param string $message Message.
 * @param array  $context Context.
 *
 * @return void
 */
private function log( string $level, string $channel, string $message, array $context = [] ): void {
	global $wpdb;

	$wpdb->insert(
		$wpdb->prefix . 'onkupon_agent_logs',
		[
			'level'        => sanitize_key( $level ),
			'channel'      => sanitize_key( $channel ),
			'message'      => sanitize_text_field( $message ),
			'context_json' => wp_json_encode( $context ),
			'created_at'   => current_time( 'mysql' ),
		],
		[ '%s', '%s', '%s', '%s', '%s' ]
	);
}
```

}
