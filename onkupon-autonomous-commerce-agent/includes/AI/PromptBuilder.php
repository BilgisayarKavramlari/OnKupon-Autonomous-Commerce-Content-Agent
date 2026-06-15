<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Plugin;

class PromptBuilder {
    public function article_prompt( array $products, array $research, array $strategy = [] ): string {
        $min_words = max( 50, absint( Plugin::settings()['min_article_words'] ?? 350 ) );
        $payload = [
            'instruction' => 'Return JSON only for a factual OnKupon editorial article. Do not use markdown fences. Do not include prose outside JSON. Do not create customer reviews, fake ratings, medical/legal/financial guarantees, or unsupported claims.',
            'body_requirements' => [
                'body must be at least ' . $min_words . ' words',
                'for Turkish content, write natural Turkish paragraphs with clear headings',
                'include concise_answer near the top',
                'include product-aware explanation sections that naturally reference relevant OnKupon products',
                'include FAQ entries when appropriate',
                'include a clear call to action',
            ],
            'score_requirements' => [
                'quality_score must be a 0-100 integer',
                'risk_score must be a 0-100 integer',
            ],
            'min_article_words' => $min_words,
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
            'required' => [ 'seo_title', 'slug', 'meta_description', 'excerpt', 'body', 'concise_answer', 'faq', 'sources', 'tags', 'categories', 'related_product_ids', 'quality_score', 'risk_score' ],
            'properties' => [
                'seo_title' => [ 'type' => 'string' ],
                'slug' => [ 'type' => 'string' ],
                'meta_description' => [ 'type' => 'string' ],
                'excerpt' => [ 'type' => 'string' ],
                'body' => [ 'type' => 'string' ],
                'concise_answer' => [ 'type' => 'string' ],
                'faq' => [ 'type' => 'array' ],
                'sources' => [ 'type' => 'array' ],
                'tags' => [ 'type' => 'array' ],
                'categories' => [ 'type' => 'array' ],
                'related_product_ids' => [ 'type' => 'array' ],
                'quality_score' => [ 'type' => 'number' ],
                'risk_score' => [ 'type' => 'number' ],
            ],
        ];
    }
}
