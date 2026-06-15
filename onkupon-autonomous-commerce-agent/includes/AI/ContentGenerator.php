<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Woo\ProductRepository;

class ContentGenerator {
    public function generate_next(): array {
        $products = array_map(
            static fn( $product ) => [ 'id' => $product->get_id(), 'name' => $product->get_name(), 'url' => get_permalink( $product->get_id() ), 'price' => $product->get_price() ],
            array_slice( ( new ProductRepository() )->active( 10 ), 0, 5 )
        );
        $builder = new PromptBuilder();
        $prompt = $builder->article_prompt( $products, [], [] );
        return ( new OpenAIProvider() )->generateJson( $prompt, $builder->article_schema() );
    }
}
