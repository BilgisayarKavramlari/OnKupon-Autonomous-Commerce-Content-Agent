<?php
namespace OnKupon\Agent\AI;

class PromptBuilder {
    public function article_prompt( array $products, array $research, array $strategy = [] ): string {
        $payload = [
            'instruction' => 'Return strict JSON only for a factual OnKupon editorial article. Do not create customer reviews, fake ratings, medical/legal/financial guarantees, or unsupported claims.',
            'required_fields' => array_keys( $this->article_schema()['properties'] ),
            'products' => $products,
            'research' => $research,
            'strategy' => $strategy,
        ];
        return wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
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
