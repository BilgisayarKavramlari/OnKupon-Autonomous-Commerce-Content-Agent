<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Logging\ActionTimelineRepository;
use OnKupon\Agent\Woo\ProductRepository;

class ContentGenerator {
    public function generate_next(): array {
        $timeline = new ActionTimelineRepository();
        $timeline->record( 'content_generation', 'started', [ 'notes' => 'Content generation started' ] );
        $products = array_map(
            static fn( $product ) => [ 'id' => $product->get_id(), 'name' => $product->get_name(), 'url' => get_permalink( $product->get_id() ), 'price' => $product->get_price() ],
            array_slice( ( new ProductRepository() )->active( 10 ), 0, 5 )
        );
        $timeline->record( 'content_generation', 'products_loaded', [ 'notes' => count( $products ) . ' products loaded', 'metadata' => [ 'product_count' => count( $products ) ] ] );
        $builder = new PromptBuilder();
        $prompt = $builder->article_prompt( $products, [], [] );
        $timeline->record( 'content_generation', 'ai_requested', [ 'notes' => 'OpenAI-compatible provider called' ] );
        $article = ( new OpenAIProvider() )->generateJson( $prompt, $builder->article_schema() );
        $timeline->record( 'content_generation', $article ? 'ai_completed' : 'failed', [ 'notes' => $article ? 'AI returned valid JSON' : 'AI returned invalid or empty JSON' ] );
        return $article;
    }
}
