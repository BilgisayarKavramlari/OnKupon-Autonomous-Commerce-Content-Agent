<?php
namespace OnKupon\Agent\Publishing;

use OnKupon\Agent\Plugin;

class CategoryManager {
    public function assign( int $post_id, array $article ): array {
        $settings = Plugin::settings();
        $fallback = absint( $settings['default_post_category_id'] ?? get_option( 'default_category' ) );
        $allowed = array_filter( array_map( 'absint', explode( ',', (string) ( $settings['allowed_category_ids'] ?? '' ) ) ) );
        $ids = [];
        foreach ( (array) ( $article['categories'] ?? [] ) as $category ) {
            $term = is_numeric( $category ) ? get_term( absint( $category ), 'category' ) : get_term_by( 'name', sanitize_text_field( (string) $category ), 'category' );
            if ( $term && ! is_wp_error( $term ) ) {
                $ids[] = (int) $term->term_id;
            }
        }
        if ( $allowed ) {
            $ids = array_values( array_intersect( $ids, $allowed ) );
        }
        if ( ! $ids && $fallback ) {
            $ids[] = $fallback;
        }
        if ( $ids ) {
            wp_set_post_categories( $post_id, array_unique( $ids ) );
        }
        return $ids;
    }
}
