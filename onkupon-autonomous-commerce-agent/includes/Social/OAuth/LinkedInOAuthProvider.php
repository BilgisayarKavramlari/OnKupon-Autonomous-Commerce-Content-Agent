<?php
namespace OnKupon\Agent\Social\OAuth;

class LinkedInOAuthProvider {
    public function authorization_url(): string {
        $state = wp_generate_password( 24, false );
        set_transient( 'onkupon_agent_oauth_state_linkedin', $state, 10 * MINUTE_IN_SECONDS );
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        return add_query_arg( [ 'response_type' => 'code', 'client_id' => sanitize_text_field( (string) ( $settings['linkedin']['client_id'] ?? '' ) ), 'redirect_uri' => rest_url( 'onkupon-agent/v1/oauth/linkedin/callback' ), 'state' => $state, 'scope' => 'w_member_social w_organization_social' ], 'https://www.linkedin.com/oauth/v2/authorization' );
    }

    public function exchange_code( string $code ): array {
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        $response = wp_remote_post( 'https://www.linkedin.com/oauth/v2/accessToken', [ 'timeout' => 20, 'body' => [ 'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => rest_url( 'onkupon-agent/v1/oauth/linkedin/callback' ), 'client_id' => (string) ( $settings['linkedin']['client_id'] ?? '' ), 'client_secret' => (string) ( $settings['linkedin']['client_secret'] ?? '' ) ] ] );
        return is_wp_error( $response ) ? [] : (array) json_decode( wp_remote_retrieve_body( $response ), true );
    }
}
