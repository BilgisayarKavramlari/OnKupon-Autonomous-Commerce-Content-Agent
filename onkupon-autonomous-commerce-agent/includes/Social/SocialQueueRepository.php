<?php
namespace OnKupon\Agent\Social;

use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Logging\ActionTimelineRepository;

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
        $id = (int) $wpdb->insert_id;
        ( new ActionTimelineRepository() )->record( 'social_post_queued', 'queued', [ 'object_type' => 'social_queue', 'object_id' => $id, 'notes' => 'Social post queued', 'metadata' => [ 'platform' => $post->platform, 'article_id' => $post->post_id, 'utm_url' => get_permalink( $post->post_id ) ] ] );
        return $id;
    }

    public function publish_due(): void {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}onkupon_agent_social_queue WHERE status='queued' AND scheduled_at <= NOW() LIMIT 20" );
        foreach ( $rows as $row ) {
            $provider = $this->provider_for( (string) $row->platform );
            if ( ! $provider || ! $provider->validateConnection() ) {
                $wpdb->update( $wpdb->prefix . 'onkupon_agent_social_queue', [ 'status' => 'failed', 'last_error' => 'Provider not configured', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => (int) $row->id ] );
                ( new ActionTimelineRepository() )->record( 'social_post_failed', 'failed', [ 'object_type' => 'social_queue', 'object_id' => (int) $row->id, 'notes' => 'Provider not configured', 'metadata' => [ 'platform' => $row->platform ] ] );
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
            ( new ActionTimelineRepository() )->record( 'social_post_published', sanitize_key( $result['status'] ?? 'published' ), [ 'object_type' => 'social_queue', 'object_id' => (int) $row->id, 'notes' => 'Social post processed', 'metadata' => [ 'platform' => $row->platform, 'remote_post_id' => $result['remote_id'] ?? '', 'remote_url' => $result['url'] ?? '' ] ] );
        }
    }

    public function create_dry_run_for_latest_post(): int {
        global $wpdb;
        $post_id = (int) $wpdb->get_var( "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID=pm.post_id AND pm.meta_key='_onkupon_agent_generated' WHERE p.post_type='post' AND p.post_status='publish' ORDER BY p.ID DESC LIMIT 1" );
        if ( ! $post_id ) {
            ( new Logger() )->log( 'warning', 'social', 'Social dry run skipped because no published OnKupon Agent post exists' );
            return 0;
        }
        $first_id = 0;
        foreach ( [ 'linkedin', 'x', 'facebook', 'quora_suggestion' ] as $platform ) {
            $id = $this->queue( new SocialPost( $platform, '[' . $platform . ' dry run] ' . get_the_title( $post_id ) . ' ' . get_permalink( $post_id ), $post_id ) );
            $first_id = $first_id ?: $id;
            $wpdb->update( $wpdb->prefix . 'onkupon_agent_social_queue', [ 'status' => 'dry_run', 'last_error' => 'Dry run only; no external provider called.', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $id ] );
            ( new ActionTimelineRepository() )->record( 'social_dry_run', 'dry_run', [ 'object_type' => 'social_queue', 'object_id' => $id, 'notes' => 'Dry-run social queue item created without external posting', 'metadata' => [ 'platform' => $platform, 'post_id' => $post_id, 'post_url' => get_permalink( $post_id ) ] ] );
        }
        return $first_id;
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
