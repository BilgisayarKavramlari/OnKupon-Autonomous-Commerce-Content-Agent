<?php
namespace OnKupon\Agent\Social\OAuth;

class XOAuthProvider {
    public function authorization_url(): string {
        $state = wp_generate_password( 24, false );
        set_transient( 'onkupon_agent_oauth_state_x', $state, 10 * MINUTE_IN_SECONDS );
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        return add_query_arg( [ 'response_type' => 'code', 'client_id' => sanitize_text_field( (string) ( $settings['x']['client_id'] ?? '' ) ), 'redirect_uri' => rest_url( 'onkupon-agent/v1/oauth/x/callback' ), 'state' => $state, 'scope' => 'tweet.read tweet.write users.read offline.access' ], 'https://twitter.com/i/oauth2/authorize' );
    }

    public function exchange_code( string $code ): array {
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        $response = wp_remote_post( 'https://api.twitter.com/2/oauth2/token', [ 'timeout' => 20, 'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ], 'body' => [ 'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => rest_url( 'onkupon-agent/v1/oauth/x/callback' ), 'client_id' => (string) ( $settings['x']['client_id'] ?? '' ), 'client_secret' => (string) ( $settings['x']['client_secret'] ?? '' ) ] ] );
        return is_wp_error( $response ) ? [] : (array) json_decode( wp_remote_retrieve_body( $response ), true );
    }
}
