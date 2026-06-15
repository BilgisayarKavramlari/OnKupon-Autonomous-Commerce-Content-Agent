<?php
namespace OnKupon\Agent\Woo;

class ProductRepository {
    public function active( int $limit = 50 ): array {
        if ( ! function_exists( 'wc_get_products' ) ) {
            return [];
        }
        return wc_get_products( [ 'status' => 'publish', 'limit' => $limit, 'return' => 'objects' ] );
    }
}
