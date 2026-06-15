<?php
namespace OnKupon\Agent\Admin;

class IntegrationsPage extends BasePage {
    public function render(): void {
        $this->header( __( 'Integrations', 'onkupon-agent' ) );
        $sections = [ 'OpenAI-compatible provider', 'RSS and official search APIs', 'LinkedIn', 'X', 'Facebook Page', 'Instagram', 'Manual Quora suggestions', 'Google Analytics 4', 'Search Console', 'WooCommerce review requests' ];
        echo '<ul class="oka-integration-list">';
        foreach ( $sections as $section ) {
            echo '<li><strong>' . esc_html( $section ) . '</strong> — ' . esc_html__( 'Configure related secrets through constants/environment variables or Settings.', 'onkupon-agent' ) . '</li>';
        }
        echo '</ul>';
        $this->footer();
    }
}
