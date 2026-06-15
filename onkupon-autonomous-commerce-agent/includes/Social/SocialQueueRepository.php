<?php
namespace OnKupon\Agent\Social;

use OnKupon\Agent\Logging\Logger;

class SocialQueueRepository {
    public function queue( SocialPost $post, ?string $scheduled_at = null ): int {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'onkupon_agent_social_queue',
            [
                'social_uuid'    => wp_generate_uuid4(),
                'post_id'        => absint( $post->post_id ),
                'platform'       => sanitize_key( $post->platform ),
                'status'         => 'queued',
                'scheduled_at'   => $scheduled_at ?: current_time( 'mysql' ),
                'message'        => sanitize_textarea_field( $post->message ),
                'media_ids_json' => wp_json_encode( array_map( 'absint', $post->media_ids ) ),
                'retry_count'    => 0,
                'metrics_json'   => wp_json_encode( [] ),
                'created_at'     => current_time( 'mysql' ),
                'updated_at'     => current_time( 'mysql' ),
            ]
        );
        return (int) $wpdb->insert_id;
    }

    public function publish_due(): void {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}onkupon_agent_social_queue WHERE status='queued' AND scheduled_at <= NOW() LIMIT 20" );
        foreach ( $rows as $row ) {
            $provider = $this->provider_for( (string) $row->platform );
            if ( ! $provider || ! $provider->validateConnection() ) {
                $wpdb->update( $wpdb->prefix . 'onkupon_agent_social_queue', [ 'status' => 'failed', 'last_error' => 'Provider not configured', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => (int) $row->id ] );
                continue;
            }
            $result = $provider->publish( new SocialPost( (string) $row->platform, (string) $row->message, (int) $row->post_id ) );
            $wpdb->update(
                $wpdb->prefix . 'onkupon_agent_social_queue',
                [
                    'status' => sanitize_key( $result['status'] ?? 'published' ),
                    'published_at' => current_time( 'mysql' ),
                    'remote_post_id' => sanitize_text_field( $result['remote_id'] ?? '' ),
                    'remote_url' => esc_url_raw( $result['url'] ?? '' ),
                    'updated_at' => current_time( 'mysql' ),
                ],
                [ 'id' => (int) $row->id ]
            );
            ( new Logger() )->log( 'info', 'social', 'Social post processed', [ 'platform' => $row->platform, 'queue_id' => $row->id ] );
        }
    }

    private function provider_for( string $platform ): ?SocialProviderInterface {
        return match ( $platform ) {
            'linkedin' => new LinkedInProvider(),
            'x' => new XProvider(),
            'facebook' => new FacebookPageProvider(),
            'instagram' => new InstagramProvider(),
            'quora_suggestion' => new ManualQuoraSuggestionProvider(),
            default => null,
        };
    }
}
