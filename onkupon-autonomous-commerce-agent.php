
<?php
/**
 * Plugin Name: OnKupon Autonomous Commerce Agent
 * Description: Fully automated AI content, commerce, social publishing, analytics, and learning agent for WooCommerce-powered WordPress sites.
 * Version: 0.1.0
 * Author: OnKupon / OptiWisdom
 * Text Domain: onkupon-autonomous-commerce-agent
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * WC requires at least: 7.0
 *
 * @package OnKuponAutonomousAgent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONKUPON_AGENT_VERSION', '0.1.0' );
define( 'ONKUPON_AGENT_FILE', __FILE__ );
define( 'ONKUPON_AGENT_DIR', plugin_dir_path( __FILE__ ) );
define( 'ONKUPON_AGENT_URL', plugin_dir_url( __FILE__ ) );

$autoload = ONKUPON_AGENT_DIR . 'vendor/autoload.php';

if ( file_exists( $autoload ) ) {
	require_once $autoload;
} else {
	require_once ONKUPON_AGENT_DIR . 'includes/Activator.php';
	require_once ONKUPON_AGENT_DIR . 'includes/Plugin.php';
}

register_activation_hook(
	__FILE__,
	static function () {
		\OnKupon\AutonomousAgent\Activator::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		if ( class_exists( '\OnKupon\AutonomousAgent\Plugin' ) ) {
			\OnKupon\AutonomousAgent\Plugin::deactivate();
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( '\OnKupon\AutonomousAgent\Plugin' ) ) {
			\OnKupon\AutonomousAgent\Plugin::instance()->run();
		}
	}
);
