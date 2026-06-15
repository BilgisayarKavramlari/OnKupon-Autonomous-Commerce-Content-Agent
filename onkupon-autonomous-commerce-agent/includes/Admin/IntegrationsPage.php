<?php
namespace OnKupon\Agent\Admin;

use OnKupon\Agent\Social\OAuth\LinkedInOAuthProvider;
use OnKupon\Agent\Social\OAuth\SocialOAuthManager;
use OnKupon\Agent\Social\OAuth\XOAuthProvider;

class IntegrationsPage extends BasePage {
    public function render(): void {
        $this->header( __( 'Integrations', 'onkupon-agent' ) );
        echo '<h2>' . esc_html__( 'Social Accounts', 'onkupon-agent' ) . '</h2>';
        $rows = [];
        $providers = [
            'linkedin' => [ 'label' => 'LinkedIn', 'url' => ( new LinkedInOAuthProvider() )->authorization_url(), 'redirect' => rest_url( 'onkupon-agent/v1/oauth/linkedin/callback' ), 'fields' => 'client_id, client_secret, posting_mode, organization_urn' ],
            'x' => [ 'label' => 'X', 'url' => ( new XOAuthProvider() )->authorization_url(), 'redirect' => rest_url( 'onkupon-agent/v1/oauth/x/callback' ), 'fields' => 'client_id, client_secret, x_text_only_mode, x_allow_url_posts, x_daily_cost_limit, x_daily_post_limit' ],
        ];
        foreach ( $providers as $provider => $config ) {
            $status = SocialOAuthManager::masked_status( $provider );
            $rows[] = [
                esc_html( $config['label'] ),
                $status['connected'] ? 'connected' : 'not connected',
                esc_html( $status['token'] ),
                '<code>' . esc_html( $config['redirect'] ) . '</code>',
                esc_html( $config['fields'] ),
                '<a class="button" href="' . esc_url( $config['url'] ) . '">' . esc_html__( 'Connect', 'onkupon-agent' ) . '</a> <button class="button" disabled>' . esc_html__( 'Disconnect', 'onkupon-agent' ) . '</button> <button class="button" disabled>' . esc_html__( 'Refresh token', 'onkupon-agent' ) . '</button> <button class="button" disabled>' . esc_html__( 'Test connection', 'onkupon-agent' ) . '</button>',
            ];
        }
        $this->table( [ 'Provider', 'Token status', 'Masked token', 'Redirect URI', 'Admin fields', 'Actions' ], $rows );
        echo '<p>' . esc_html__( 'OAuth/API integrations never use browser-login automation, stored social passwords, browser cookies, or scraping-based posting. Tokens are masked in the UI and raw tokens are never logged.', 'onkupon-agent' ) . '</p>';
        echo '<h2>' . esc_html__( 'Other Integrations', 'onkupon-agent' ) . '</h2><ul class="oka-integration-list">';
        foreach ( [ 'OpenAI-compatible provider', 'RSS and official search APIs', 'Facebook Page', 'Instagram', 'Manual Quora suggestions', 'Google Analytics 4', 'Search Console', 'WooCommerce review requests' ] as $section ) {
            echo '<li><strong>' . esc_html( $section ) . '</strong> — ' . esc_html__( 'Configure related secrets through constants/environment variables or Settings.', 'onkupon-agent' ) . '</li>';
        }
        echo '</ul>';
        $this->footer();
    }
}
