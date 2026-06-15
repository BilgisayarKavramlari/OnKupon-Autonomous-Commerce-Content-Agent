<?php
namespace OnKupon\Agent\Publishing;

use OnKupon\Agent\AI\ContentValidator;
use OnKupon\Agent\Logging\ActionTimelineRepository;
use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Plugin;
use OnKupon\Agent\SEO\SEOManager;
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
            $this->record_rejected_attempt( $article, $validation );
            ( new Logger() )->log( 'info', 'social', 'Social sharing skipped because article was not published', [ 'final_status' => 'rejected', 'post_id' => 0 ] );
            ( new ActionTimelineRepository() )->record( 'social_sharing', 'skipped', [ 'notes' => 'Social sharing skipped because article was not published', 'metadata' => [ 'final_status' => 'rejected' ] ] );
            return 0;
        }
        $article = $validation['article'] ?? $article;
        $formatted = ( new ContentFormatter() )->format( $article );
        if ( ( new ContentFormatter() )->has_raw_markdown( $formatted ) ) {
            $validation['errors'][] = 'Formatted content still contains raw Markdown';
            $validation['diagnostics']['body_preview'] = sanitize_textarea_field( wp_strip_all_tags( $formatted ) );
            $this->record_rejected_attempt( $article, $validation );
            return 0;
        }
        $post_id = wp_insert_post(
            [
                'post_title'   => sanitize_text_field( $article['seo_title'] ),
                'post_name'    => sanitize_title( $article['slug'] ),
                'post_excerpt' => sanitize_text_field( $article['excerpt'] ),
                'post_content' => $formatted,
                'post_status'  => 'publish',
                'post_type'    => 'post',
                'post_author'  => ( new AuthorManager() )->author_id(),
                'meta_input'   => [
                    '_onkupon_agent_generated' => 1,
                    '_onkupon_quality_score'   => (float) $article['quality_score'],
                    '_onkupon_risk_score'      => (float) $article['risk_score'],
                    '_onkupon_related_products'=> wp_json_encode( array_map( 'absint', $article['related_product_ids'] ?? [] ) ),
                    '_onkupon_agent_faq'       => wp_json_encode( (array) ( $article['faq'] ?? [] ) ),
                ],
            ],
            true
        );
        if ( is_wp_error( $post_id ) ) {
            ( new Logger() )->log( 'error', 'publishing', 'Post creation failed', [ 'error' => $post_id->get_error_message() ] );
            return 0;
        }
        $categories = ( new CategoryManager() )->assign( (int) $post_id, $article );
        $tags = ( new TagManager() )->assign( (int) $post_id, $article );
        ( new FeaturedImageManager() )->assign( (int) $post_id, $article );
        ( new SEOManager() )->apply( (int) $post_id, $article );
        $this->queue_social_posts( (int) $post_id, $article );
        ( new Logger() )->log( 'info', 'social', 'Social posts queued for published article', [ 'post_id' => (int) $post_id, 'platforms' => [ 'linkedin', 'x', 'facebook', 'instagram', 'quora_suggestion' ] ] );
        ( new ActionTimelineRepository() )->record(
            'post_published',
            'published',
            [
                'object_type' => 'post',
                'object_id' => (int) $post_id,
                'notes' => 'Article published',
                'metadata' => [
                    'post_url' => get_permalink( (int) $post_id ),
                    'word_count' => $validation['diagnostics']['word_count'] ?? 0,
                    'quality_score' => $article['quality_score'] ?? null,
                    'risk_score' => $article['risk_score'] ?? null,
                    'categories' => $categories,
                    'tags' => $tags,
                    'related_products' => array_map( 'absint', (array) ( $article['related_product_ids'] ?? [] ) ),
                    'social_queue_status' => 'queued',
                ],
            ]
        );
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

    private function record_rejected_attempt( array $article, array $validation ): void {
        global $wpdb;

        $diagnostics = (array) ( $validation['diagnostics'] ?? [] );
        $errors = array_values( (array) ( $validation['errors'] ?? [] ) );
        $metadata = [
            'seo_title' => sanitize_text_field( (string) ( $article['seo_title'] ?? $article['title'] ?? '' ) ),
            'slug' => sanitize_title( (string) ( $article['slug'] ?? '' ) ),
            'word_count' => absint( $diagnostics['word_count'] ?? 0 ),
            'body_char_length' => absint( $diagnostics['body_char_length'] ?? 0 ),
            'target_article_words' => absint( $diagnostics['target_article_words'] ?? 0 ),
            'section_count' => absint( $diagnostics['section_count'] ?? 0 ),
            'faq_count' => absint( $diagnostics['faq_count'] ?? 0 ),
            'product_link_count' => absint( $diagnostics['product_link_count'] ?? 0 ),
            'retry_count' => absint( $article['_onkupon_retry_count'] ?? 0 ),
            'expansion_attempted' => ! empty( $article['_onkupon_expansion_attempted'] ),
            'final_status' => 'rejected',
            'validation_status' => 'rejected',
            'final_word_count' => absint( $diagnostics['word_count'] ?? 0 ),
            'quality_score' => isset( $diagnostics['quality_score'] ) ? (float) $diagnostics['quality_score'] : null,
            'risk_score' => isset( $diagnostics['risk_score'] ) ? (float) $diagnostics['risk_score'] : null,
            'related_product_ids' => array_map( 'absint', (array) ( $diagnostics['related_product_ids'] ?? $article['related_product_ids'] ?? [] ) ),
            'body_preview' => sanitize_textarea_field( (string) ( $diagnostics['body_preview'] ?? '' ) ),
            'model' => sanitize_text_field( (string) ( Plugin::settings()['openai_model'] ?? '' ) ),
            'run_uuid' => sanitize_text_field( (string) ( $article['run_uuid'] ?? $article['_onkupon_run_uuid'] ?? '' ) ),
            'prompt_version' => 'structured-v2',
            'rejection_reasons' => $errors,
        ];

        $wpdb->insert(
            $wpdb->prefix . 'onkupon_agent_actions',
            [
                'action_uuid' => wp_generate_uuid4(),
                'run_uuid' => $metadata['run_uuid'] ?: null,
                'action_type' => 'content_generation',
                'object_type' => 'article_candidate',
                'object_id' => absint( $article['candidate_id'] ?? 0 ),
                'status' => 'rejected',
                'input_hash' => null,
                'output_hash' => hash( 'sha256', wp_json_encode( $article ) ?: '' ),
                'created_at' => current_time( 'mysql' ),
                'completed_at' => current_time( 'mysql' ),
                'error_message' => implode( '; ', array_map( 'sanitize_text_field', $errors ) ),
                'metadata_json' => wp_json_encode( $metadata ),
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
    }
}
