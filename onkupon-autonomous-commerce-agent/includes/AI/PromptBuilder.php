<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Plugin;

class PromptBuilder {
    public function article_prompt( array $products, array $research, array $strategy = [] ): string {
        $settings = Plugin::settings();
        $min_words = max( 50, absint( $settings['min_article_words'] ?? 600 ) );
        $target_words = max( $min_words, absint( $settings['target_article_words'] ?? 900 ) );
        $payload = [
            'instruction' => 'Return JSON only for a factual OnKupon editorial article. Do not use markdown fences. Do not include prose outside JSON. Do not create customer reviews, fake ratings, medical/legal/financial guarantees, or unsupported claims.',
            'body_requirements' => [
                'structured sections, concise_answer, FAQ, and CTA together must target ' . $target_words . ' words and never be below ' . $min_words . ' words',
                'use Turkish if content_language is tr; write natural Turkish paragraphs with clear headings',
                'include concise_answer near the top',
                'include product-aware explanation sections that naturally reference relevant OnKupon products',
                'include FAQ entries when appropriate',
                'include a clear call to action',
                'use real product names and product URLs from provided product data; do not invent unavailable products',
            ],
            'score_requirements' => [
                'quality_score must be a 0-100 integer',
                'risk_score must be a 0-100 integer',
            ],
            'min_article_words' => $min_words,
            'target_article_words' => $target_words,
            'max_article_words' => absint( $settings['max_article_words'] ?? 1400 ),
            'heading_count' => absint( $settings['heading_count'] ?? 5 ),
            'faq_count' => absint( $settings['faq_count'] ?? 4 ),
            'product_link_count' => absint( $settings['product_link_count'] ?? 3 ),
            'product_card_count' => absint( $settings['product_card_count'] ?? 3 ),
            'content_language' => sanitize_key( (string) ( $settings['content_language'] ?? 'en' ) ),
            'required_fields' => array_keys( $this->article_schema()['properties'] ),
            'products' => $products,
            'research' => $research,
            'strategy' => $strategy,
        ];
        return wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    }

    public function article_schema(): array {
        return [
            'type' => 'object',
            'required' => [ 'seo_title', 'slug', 'meta_description', 'excerpt', 'focus_keyphrase', 'secondary_keyphrases', 'tags', 'categories', 'concise_answer', 'sections', 'faq', 'related_product_ids', 'product_mentions', 'product_cards', 'cta', 'social_posts', 'schema', 'quality_score', 'risk_score' ],
            'properties' => [
                'seo_title' => [ 'type' => 'string' ],
                'slug' => [ 'type' => 'string' ],
                'meta_description' => [ 'type' => 'string' ],
                'excerpt' => [ 'type' => 'string' ],
                'focus_keyphrase' => [ 'type' => 'string' ],
                'secondary_keyphrases' => [ 'type' => 'array' ],
                'concise_answer' => [ 'type' => 'string' ],
                'sections' => [ 'type' => 'array' ],
                'faq' => [ 'type' => 'array' ],
                'sources' => [ 'type' => 'array' ],
                'tags' => [ 'type' => 'array' ],
                'categories' => [ 'type' => 'array' ],
                'related_product_ids' => [ 'type' => 'array' ],
                'product_mentions' => [ 'type' => 'array' ],
                'product_cards' => [ 'type' => 'array' ],
                'comparison_table' => [ 'type' => 'array' ],
                'cta' => [ 'type' => 'string' ],
                'social_posts' => [ 'type' => 'object' ],
                'schema' => [ 'type' => 'object' ],
                'quality_score' => [ 'type' => 'number' ],
                'risk_score' => [ 'type' => 'number' ],
                'suggested_featured_image_product_id' => [ 'type' => 'number' ],
            ],
        ];
    }
}
