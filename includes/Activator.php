<?php
/**
 * Plugin activation logic.
 *
 * @package OnKuponAutonomousAgent
 */

namespace OnKupon\AutonomousAgent;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator class.
 */
final class Activator {

	/**
	 * Runs on plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		self::create_tables();

		update_option( 'onkupon_agent_status', 'paused', false );
		update_option( 'onkupon_agent_version', ONKUPON_AGENT_VERSION, false );
	}

	/**
	 * Creates custom database tables.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$tables = [];

		$tables[] = "CREATE TABLE {$wpdb->prefix}onkupon_agent_runs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			run_uuid VARCHAR(64) NOT NULL,
			run_type VARCHAR(80) NOT NULL,
			status VARCHAR(40) NOT NULL DEFAULT 'pending',
			started_at DATETIME NULL,
			finished_at DATETIME NULL,
			duration_ms BIGINT UNSIGNED DEFAULT 0,
			items_processed BIGINT UNSIGNED DEFAULT 0,
			items_published BIGINT UNSIGNED DEFAULT 0,
			items_skipped BIGINT UNSIGNED DEFAULT 0,
			error_count BIGINT UNSIGNED DEFAULT 0,
			token_cost_estimate DECIMAL(12,6) DEFAULT 0,
			notes LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY run_uuid (run_uuid),
			KEY status (status),
			KEY run_type (run_type)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}onkupon_agent_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			level VARCHAR(20) NOT NULL DEFAULT 'info',
			channel VARCHAR(80) NOT NULL DEFAULT 'system',
			message TEXT NOT NULL,
			context_json LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY level (level),
			KEY channel (channel),
			KEY created_at (created_at)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}onkupon_agent_product_scores (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			product_name TEXT NULL,
			product_url TEXT NULL,
			product_status VARCHAR(40) NULL,
			content_score DECIMAL(10,4) DEFAULT 0,
			trend_score DECIMAL(10,4) DEFAULT 0,
			revenue_score DECIMAL(10,4) DEFAULT 0,
			freshness_score DECIMAL(10,4) DEFAULT 0,
			last_content_at DATETIME NULL,
			last_social_at DATETIME NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			metadata_json LONGTEXT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY product_id (product_id)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}onkupon_agent_social_queue (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			social_uuid VARCHAR(64) NOT NULL,
			post_id BIGINT UNSIGNED NULL,
			platform VARCHAR(40) NOT NULL,
			account_id VARCHAR(120) NULL,
			status VARCHAR(40) NOT NULL DEFAULT 'queued',
			scheduled_at DATETIME NULL,
			published_at DATETIME NULL,
			remote_post_id VARCHAR(191) NULL,
			remote_url TEXT NULL,
			message LONGTEXT NULL,
			retry_count INT UNSIGNED DEFAULT 0,
			last_error TEXT NULL,
			metrics_json LONGTEXT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY social_uuid (social_uuid),
			KEY post_id (post_id),
			KEY platform (platform),
			KEY status (status)
		) {$charset_collate};";

		$tables[] = "CREATE TABLE {$wpdb->prefix}onkupon_agent_learning_weights (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			dimension VARCHAR(80) NOT NULL,
			variant_key VARCHAR(191) NOT NULL,
			weight DECIMAL(12,6) DEFAULT 1,
			impressions BIGINT UNSIGNED DEFAULT 0,
			clicks BIGINT UNSIGNED DEFAULT 0,
			conversions BIGINT UNSIGNED DEFAULT 0,
			engagement_score DECIMAL(12,6) DEFAULT 0,
			revenue_score DECIMAL(12,6) DEFAULT 0,
			last_used_at DATETIME NULL,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			metadata_json LONGTEXT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY dimension_variant (dimension, variant_key)
		) {$charset_collate};";

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}
	}
}
