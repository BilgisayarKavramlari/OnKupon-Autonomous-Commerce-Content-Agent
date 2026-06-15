<?php
namespace OnKupon\Agent\Publishing;

use OnKupon\Agent\AI\ContentValidator;
use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Plugin;
use OnKupon\Agent\Social\SocialQueueRepository;
use OnKupon\Agent\Social\SocialPost;

class WordPressPublisher {
    public function publish( array $article ): int {
        if ( ! Plugin::can_publish() ) {
            return 0;
        }
        $validation = ( new ContentValidator() )->validate( $article );
        if ( ! $validation['valid'] ) {
            ( new Logger() )->log( 'warning', 'validation', 'Article rejected', $validation['diagnostics'] ?? [ 'errors' => $validation['errors'] ] );
            return 0;
        }
        $article = $validation['article'] ?? $article;
        $post_id = wp_insert_post(
            [
                'post_title'   => sanitize_text_field( $article['seo_title'] ),
                'post_name'    => sanitize_title( $article['slug'] ),
                'post_excerpt' => sanitize_text_field( $article['excerpt'] ),
                'post_content' => $validation['sanitized'],
                'post_status'  => 'publish',
                'post_type'    => 'post',
                'meta_input'   => [
                    '_onkupon_agent_generated' => 1,
                    '_onkupon_quality_score'   => (float) $article['quality_score'],
                    '_onkupon_risk_score'      => (float) $article['risk_score'],
                    '_onkupon_related_products'=> wp_json_encode( array_map( 'absint', $article['related_product_ids'] ?? [] ) ),
                ],
            ],
            true
        );
        if ( is_wp_error( $post_id ) ) {
            ( new Logger() )->log( 'error', 'publishing', 'Post creation failed', [ 'error' => $post_id->get_error_message() ] );
            return 0;
        }
        $this->queue_social_posts( (int) $post_id, $article );
        return (int) $post_id;
    }

    private function queue_social_posts( int $post_id, array $article ): void {
        $url = get_permalink( $post_id );
        $title = sanitize_text_field( $article['seo_title'] ?? get_the_title( $post_id ) );
        $queue = new SocialQueueRepository();
        foreach ( [ 'linkedin', 'x', 'facebook', 'instagram', 'quora_suggestion' ] as $platform ) {
            $message = $platform === 'x' ? wp_trim_words( $title . ' ' . $url, 35, '' ) : $title . "\n" . $url;
            $queue->queue( new SocialPost( $platform, $message, $post_id ) );
        }
    }
}
