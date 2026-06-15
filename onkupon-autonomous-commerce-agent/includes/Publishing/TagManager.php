<?php
namespace OnKupon\Agent\Publishing;

class TagManager {
    public function assign( int $post_id, array $article ): array {
        $generic = [ 'best', 'cheap', 'sale', 'product', 'review', 'awesome' ];
        $tags = [];
        foreach ( (array) ( $article['tags'] ?? [] ) as $tag ) {
            $tag = sanitize_text_field( (string) $tag );
            if ( '' === $tag || in_array( strtolower( $tag ), $generic, true ) ) {
                continue;
            }
            $tags[] = $tag;
        }
        $tags = array_slice( array_values( array_unique( $tags ) ), 0, 10 );
        if ( $tags ) {
            wp_set_post_tags( $post_id, $tags, false );
        }
        return $tags;
    }
}
