<?php
namespace OnKupon\Agent\Admin;

use OnKupon\Agent\Social\OAuth\SocialOAuthManager;

class IntegrationsPage extends BasePage {
    public function render(): void {
        $this->header( __( 'Integrations', 'onkupon-agent' ) );
        $sections = [ 'OpenAI-compatible provider', 'RSS and official search APIs', 'Facebook Page', 'Instagram', 'Manual Quora suggestions', 'Google Analytics 4', 'Search Console', 'WooCommerce review requests' ];
        echo '<h2>' . esc_html__( 'Social Accounts', 'onkupon-agent' ) . '</h2>';
        $rows = [];
        foreach ( [ 'linkedin' => 'LinkedIn', 'x' => 'X' ] as $provider => $label ) {
            $status = SocialOAuthManager::masked_status( $provider );
            $rows[] = [ $label, $status['connected'] ? 'connected' : 'not connected', esc_html( $status['token'] ), rest_url( 'onkupon-agent/v1/oauth/' . $provider . '/callback' ), esc_html__( 'Connect with OAuth, disconnect, refresh token, and test connection are provider-API tasks; no browser-login automation, passwords, cookies, or scraping are supported.', 'onkupon-agent' ) ];
        }
        $this->table( [ 'Provider', 'Token status', 'Masked token', 'Redirect URI', 'Safety policy' ], $rows );
        echo '<h2>' . esc_html__( 'Other Integrations', 'onkupon-agent' ) . '</h2><ul class="oka-integration-list">';
        foreach ( $sections as $section ) {
            echo '<li><strong>' . esc_html( $section ) . '</strong> — ' . esc_html__( 'Configure related secrets through constants/environment variables or Settings.', 'onkupon-agent' ) . '</li>';
        }
        echo '</ul>';
        $this->footer();
    }
}
