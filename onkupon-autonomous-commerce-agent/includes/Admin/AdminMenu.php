<?php
namespace OnKupon\Agent\Admin;

use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Plugin;
use OnKupon\Agent\Scheduler\ActionSchedulerBridge;
use OnKupon\Agent\Security\CapabilityManager;
use OnKupon\Agent\Security\LockManager;

class AdminMenu {
    public function register(): void {
        add_action( 'admin_menu', [ $this, 'menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        add_action( 'admin_post_onkupon_agent_control', [ $this, 'handle_control' ] );
    }

    public function menu(): void {
        $capability = CapabilityManager::capability();
        add_menu_page( 'OnKupon Agent', 'OnKupon Agent', $capability, 'onkupon-agent', [ new DashboardPage(), 'render' ], 'dashicons-chart-line', 56 );
        $pages = [
            'Control Center'       => ControlCenterPage::class,
            'Content Timeline'     => ContentTimelinePage::class,
            'Product Intelligence' => ProductIntelligencePage::class,
            'Social Queue'         => SocialQueuePage::class,
            'Analytics'            => AnalyticsPage::class,
            'Learning'             => LearningPage::class,
            'Integrations'         => IntegrationsPage::class,
            'Logs'                 => LogsPage::class,
            'Review Integrity'     => ReviewIntegrityPage::class,
            'Settings'             => SettingsPage::class,
        ];
        foreach ( $pages as $title => $class ) {
            add_submenu_page( 'onkupon-agent', $title, $title, $capability, 'onkupon-agent-' . sanitize_title( $title ), [ new $class(), 'render' ] );
        }
    }

    public function assets( string $hook ): void {
        if ( ! str_contains( $hook, 'onkupon-agent' ) ) {
            return;
        }
        wp_enqueue_style( 'onkupon-agent', ONKUPON_AGENT_URL . 'assets/admin.css', [], ONKUPON_AGENT_VERSION );
        wp_enqueue_script( 'onkupon-agent-charts', ONKUPON_AGENT_URL . 'assets/charts.js', [], ONKUPON_AGENT_VERSION, true );
        wp_enqueue_script( 'onkupon-agent-admin', ONKUPON_AGENT_URL . 'assets/admin.js', [ 'onkupon-agent-charts' ], ONKUPON_AGENT_VERSION, true );
    }

    public function handle_control(): void {
        if ( ! current_user_can( CapabilityManager::capability() ) ) {
            wp_die( esc_html__( 'Forbidden', 'onkupon-agent' ) );
        }
        check_admin_referer( 'onkupon_agent_control' );
        $action = sanitize_key( wp_unslash( $_POST['agent_action'] ?? '' ) );
        $bridge = new ActionSchedulerBridge();

        switch ( $action ) {
            case 'start':
            case 'resume':
                Plugin::update_status( 'running' );
                break;
            case 'pause':
                Plugin::update_status( 'paused' );
                break;
            case 'stop':
                Plugin::update_status( 'stopped' );
                break;
            case 'emergency-stop':
                Plugin::update_status( 'emergency_stopped' );
                ( new LockManager() )->clear();
                break;
            case 'run-now':
                $bridge->enqueue( 'onkupon_agent_product_scan' );
                $bridge->enqueue( 'onkupon_agent_research' );
                $bridge->enqueue( 'onkupon_agent_content' );
                break;
            case 'collect-metrics':
                $bridge->enqueue( 'onkupon_agent_metrics' );
                break;
            case 'recalculate-scores':
                $bridge->enqueue( 'onkupon_agent_product_scan' );
                break;
            case 'clear-locks':
                ( new LockManager() )->clear();
                break;
            case 'safe-mode':
                $settings = Plugin::settings();
                $settings['safe_mode'] = empty( $settings['safe_mode'] );
                Plugin::save_settings( $settings );
                break;
        }

        ( new Logger() )->log( 'info', 'audit', 'Control action executed', [ 'action' => $action, 'user' => get_current_user_id() ] );
        wp_safe_redirect( add_query_arg( [ 'page' => 'onkupon-agent-control-center', 'oka_notice' => 'ok' ], admin_url( 'admin.php' ) ) );
        exit;
    }
}
