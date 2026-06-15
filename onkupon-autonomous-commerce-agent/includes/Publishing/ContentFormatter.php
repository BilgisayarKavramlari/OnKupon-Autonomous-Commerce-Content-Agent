<?php
namespace OnKupon\Agent\Publishing;

use OnKupon\Agent\Woo\ProductRepository;

class ContentFormatter {
    public function format( array $article ): string {
        $blocks = [];
        $blocks[] = $this->paragraph( '<strong>' . esc_html__( 'Quick answer:', 'onkupon-agent' ) . '</strong> ' . esc_html( (string) ( $article['concise_answer'] ?? '' ) ) );
        if ( ! empty( $article['introduction'] ) ) {
            $blocks[] = $this->paragraph( esc_html( (string) $article['introduction'] ) );
        }
        foreach ( (array) ( $article['sections'] ?? [] ) as $section ) {
            $heading = sanitize_text_field( (string) ( $section['heading'] ?? '' ) );
            $body = $this->link_products( (string) ( $section['body'] ?? '' ), (array) ( $article['product_mentions'] ?? [] ) );
            if ( $heading ) {
                $blocks[] = $this->heading( $heading );
            }
            if ( $body ) {
                $blocks[] = $this->paragraph( wp_kses_post( wpautop( $body ) ) );
            }
        }
        if ( ! empty( $article['comparison_table'] ) && is_array( $article['comparison_table'] ) ) {
            $blocks[] = $this->table( $article['comparison_table'] );
        }
        if ( ! empty( $article['product_recommendations'] ) ) {
            $blocks[] = $this->heading( __( 'Product-aware recommendations', 'onkupon-agent' ) );
            foreach ( (array) $article['product_recommendations'] as $recommendation ) {
                $blocks[] = $this->paragraph( $this->recommendation_html( (array) $recommendation ) );
            }
        }
        if ( ! empty( $article['product_cards'] ) ) {
            foreach ( array_slice( array_map( 'absint', (array) $article['product_cards'] ), 0, 6 ) as $product_id ) {
                $blocks[] = '<!-- wp:shortcode -->[onkupon_agent_product_card id="' . absint( $product_id ) . '"]<!-- /wp:shortcode -->';
            }
        }
        if ( ! empty( $article['faq'] ) ) {
            $blocks[] = $this->heading( __( 'Frequently asked questions', 'onkupon-agent' ) );
            foreach ( (array) $article['faq'] as $faq ) {
                $blocks[] = $this->heading( sanitize_text_field( (string) ( $faq['question'] ?? '' ) ), 3 );
                $blocks[] = $this->paragraph( esc_html( (string) ( $faq['answer'] ?? '' ) ) );
            }
        }
        if ( ! empty( $article['cta'] ) ) {
            $blocks[] = '<!-- wp:paragraph {"className":"onkupon-agent-cta"} --><p class="onkupon-agent-cta">' . esc_html( (string) $article['cta'] ) . '</p><!-- /wp:paragraph -->';
        }
        return implode( "\n\n", array_filter( $blocks ) );
    }

    public function has_raw_markdown( string $content ): bool {
        $plain = wp_strip_all_tags( preg_replace( '/href="[^"]+"/', '', $content ) );
        return (bool) preg_match( '/(^|\n)#{1,6}\s+|\[[^\]]+\]\([^\)]+\)|```|https?:\/\/\S+/u', $plain );
    }

    private function heading( string $text, int $level = 2 ): string {
        $level = max( 2, min( 4, $level ) );
        return '<!-- wp:heading {"level":' . $level . '} --><h' . $level . '>' . esc_html( $text ) . '</h' . $level . '><!-- /wp:heading -->';
    }

    private function paragraph( string $html ): string {
        return '<!-- wp:paragraph --><p>' . wp_kses_post( $html ) . '</p><!-- /wp:paragraph -->';
    }

    private function table( array $table ): string {
        $rows = [];
        if ( isset( $table['columns'] ) ) {
            $rows[] = '<tr>' . implode( '', array_map( static fn( $cell ) => '<th>' . esc_html( (string) $cell ) . '</th>', (array) $table['columns'] ) ) . '</tr>';
            $table = (array) ( $table['rows'] ?? [] );
        }
        foreach ( $table as $row ) {
            $cells = array_map( static fn( $cell ) => '<td>' . esc_html( (string) $cell ) . '</td>', (array) $row );
            $rows[] = '<tr>' . implode( '', $cells ) . '</tr>';
        }
        return '<!-- wp:table --><figure class="wp-block-table"><table><tbody>' . implode( '', $rows ) . '</tbody></table></figure><!-- /wp:table -->';
    }

    private function recommendation_html( array $recommendation ): string {
        $product_id = absint( $recommendation['product_id'] ?? 0 );
        $anchor = sanitize_text_field( (string) ( $recommendation['anchor_text'] ?? get_the_title( $product_id ) ) );
        $url = $product_id ? get_permalink( $product_id ) : '';
        $reason = esc_html( (string) ( $recommendation['reason'] ?? '' ) );
        $use_case = esc_html( (string) ( $recommendation['use_case'] ?? '' ) );
        $link = $url && 'publish' === get_post_status( $product_id ) ? '<a href="' . esc_url( $url ) . '">' . esc_html( $anchor ) . '</a>' : esc_html( $anchor );
        return '<strong>' . $link . '</strong> — ' . $reason . ( $use_case ? ' ' . esc_html__( 'Use case:', 'onkupon-agent' ) . ' ' . $use_case : '' );
    }

    private function link_products( string $text, array $mentions ): string {
        foreach ( $mentions as $mention ) {
            $product_id = absint( $mention['product_id'] ?? 0 );
            $anchor = sanitize_text_field( (string) ( $mention['anchor_text'] ?? '' ) );
            $url = $product_id ? get_permalink( $product_id ) : '';
            if ( ! $product_id || ! $anchor || ! $url || 'publish' !== get_post_status( $product_id ) ) {
                continue;
            }
            $link = '<a href="' . esc_url( add_query_arg( [ 'utm_source' => 'onkupon_agent', 'utm_medium' => 'internal_content', 'utm_campaign' => 'ai_article', 'utm_content' => 'product_' . $product_id ], $url ) ) . '">' . esc_html( $anchor ) . '</a>';
            $text = preg_replace( '/' . preg_quote( $anchor, '/' ) . '/u', $link, $text, 1 );
        }
        return $text;
    }
}
