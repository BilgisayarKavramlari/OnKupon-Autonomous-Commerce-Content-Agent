<?php
namespace OnKupon\Agent\Social\OAuth;

use OnKupon\Agent\Logging\Logger;

class SocialOAuthManager {
    public function register_routes(): void {
        add_action( 'rest_api_init', function (): void {
            register_rest_route( 'onkupon-agent/v1', '/oauth/(?P<provider>linkedin|x)/callback', [
                'methods' => 'GET',
                'callback' => [ $this, 'callback' ],
                'permission_callback' => '__return_true',
            ] );
        } );
    }

    public function callback( \WP_REST_Request $request ): \WP_REST_Response {
        $provider = sanitize_key( (string) $request['provider'] );
        $state = sanitize_text_field( (string) $request->get_param( 'state' ) );
        $expected = get_transient( 'onkupon_agent_oauth_state_' . $provider );
        if ( ! $state || ! $expected || ! hash_equals( (string) $expected, $state ) ) {
            ( new Logger() )->log( 'warning', 'social_oauth', 'OAuth callback rejected due to invalid state', [ 'provider' => $provider ] );
            return new \WP_REST_Response( [ 'error' => 'invalid_state' ], 403 );
        }
        $code = sanitize_text_field( (string) $request->get_param( 'code' ) );
        $token = 'linkedin' === $provider ? ( new LinkedInOAuthProvider() )->exchange_code( $code ) : ( new XOAuthProvider() )->exchange_code( $code );
        if ( empty( $token['access_token'] ) ) {
            ( new Logger() )->log( 'error', 'social_oauth', 'OAuth token exchange failed', [ 'provider' => $provider, 'response' => wp_json_encode( $token ) ] );
            return new \WP_REST_Response( [ 'error' => 'token_exchange_failed' ], 400 );
        }
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        $settings[ $provider ]['access_token'] = sanitize_text_field( (string) $token['access_token'] );
        $settings[ $provider ]['refresh_token'] = sanitize_text_field( (string) ( $token['refresh_token'] ?? '' ) );
        $settings[ $provider ]['expires_at'] = time() + absint( $token['expires_in'] ?? 0 );
        update_option( 'onkupon_agent_social_oauth', $settings, false );
        ( new Logger() )->log( 'info', 'social_oauth', 'OAuth token stored', [ 'provider' => $provider, 'token' => '***masked***' ] );
        return new \WP_REST_Response( [ 'status' => 'connected', 'provider' => $provider ], 200 );
    }

    public static function masked_status( string $provider ): array {
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        $token = (string) ( $settings[ $provider ]['access_token'] ?? '' );
        return [ 'connected' => '' !== $token, 'token' => $token ? substr( $token, 0, 4 ) . '…' . substr( $token, -4 ) : '' ];
    }
}
