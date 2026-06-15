<?php
namespace OnKupon\Agent\Social;

use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Security\SecretsManager;

class LinkedInProvider implements SocialProviderInterface {
    public function validateConnection(): bool {
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        return '' !== ( new SecretsManager() )->get( 'LINKEDIN_TOKEN' ) || ! empty( $settings['linkedin']['access_token'] );
    }
    public function publish( SocialPost $post ): array {
        if ( ! $this->validateConnection() ) {
            return [ 'status' => 'failed', 'remote_id' => '', 'url' => '', 'error' => 'LinkedIn OAuth token missing' ];
        }
        $settings = get_option( 'onkupon_agent_social_oauth', [] );
        $token = ( new SecretsManager() )->get( 'LINKEDIN_TOKEN' ) ?: (string) ( $settings['linkedin']['access_token'] ?? '' );
        $author = sanitize_text_field( (string) ( $settings['linkedin']['author_urn'] ?? '' ) );
        if ( '' === $author ) {
            return [ 'status' => 'failed', 'remote_id' => '', 'url' => '', 'error' => 'LinkedIn author/member or organization URN missing' ];
        }
        $response = wp_remote_post( 'https://api.linkedin.com/v2/ugcPosts', [ 'timeout' => 20, 'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json', 'X-Restli-Protocol-Version' => '2.0.0' ], 'body' => wp_json_encode( [ 'author' => $author, 'lifecycleState' => 'PUBLISHED', 'specificContent' => [ 'com.linkedin.ugc.ShareContent' => [ 'shareCommentary' => [ 'text' => $post->message ], 'shareMediaCategory' => 'NONE' ] ], 'visibility' => [ 'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC' ] ] ) ] );
        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( is_wp_error( $response ) || $code >= 300 ) {
            ( new Logger() )->log( 'error', 'social', 'LinkedIn official API post failed', [ 'http_status' => $code, 'error' => is_wp_error( $response ) ? $response->get_error_message() : wp_json_encode( $body ) ] );
            return [ 'status' => 'failed', 'remote_id' => '', 'url' => '' ];
        }
        return [ 'status' => 'published', 'remote_id' => sanitize_text_field( (string) ( $body['id'] ?? '' ) ), 'url' => '' ];
    }
    public function deleteRemotePost( string $remoteId ): bool { return false; }
    public function fetchMetrics( string $remoteId ): array { return []; }
    public function getPlatformName(): string { return 'linkedin'; }
}
