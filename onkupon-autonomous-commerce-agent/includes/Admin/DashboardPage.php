<?php
namespace OnKupon\Agent\Admin;

class DashboardPage extends BasePage {
    public function render(): void {
        global $wpdb;
        $today = current_time( 'Y-m-d' );
        $articles = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' AND post_date >= %s AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_onkupon_agent_generated')", $today . ' 00:00:00' ) );
        $social = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}onkupon_agent_social_queue WHERE status='published' AND published_at >= %s", $today . ' 00:00:00' ) );
        $errors = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}onkupon_agent_logs WHERE level IN ('error','warning') AND created_at >= %s", $today . ' 00:00:00' ) );
        $cost = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(estimated_cost),0) FROM {$wpdb->prefix}onkupon_agent_costs WHERE created_at >= %s", $today . ' 00:00:00' ) );

        $this->header( __( 'OnKupon Agent Overview', 'onkupon-agent' ) );
        $this->card_grid( [
            'Agent status' => $this->status(),
            'Articles today' => $articles,
            'Social posts today' => $social,
            'Failed/warning logs today' => $errors,
            'Estimated AI/API cost today' => '$' . number_format_i18n( $cost, 4 ),
        ] );
        echo '<h2>' . esc_html__( 'Last 7 Days', 'onkupon-agent' ) . '</h2><canvas id="okaChart" height="90"></canvas>';
        $this->footer();
    }
}
