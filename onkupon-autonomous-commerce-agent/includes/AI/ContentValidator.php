<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Plugin;

class ContentValidator {
    public function validate( array $article ): array {
        $article = $this->normalize_scores( $article );
        $schema_result = ( new JsonSchemaValidator() )->validate( $article, ( new PromptBuilder() )->article_schema() );
        $errors = $schema_result['errors'];
        $error_codes = array_fill( 0, count( $errors ), 'schema_error' );
        $body = $this->full_article_text( $article );
        $plain = wp_strip_all_tags( $body );
        $word_count = $this->word_count( $plain );
        $min_words = $this->min_article_words();

        if ( $word_count < $min_words ) {
            $errors[] = 'Content is too thin';
            $error_codes[] = 'thin_content';
        }
        if ( (float) ( $article['quality_score'] ?? 0 ) < (float) Plugin::settings()['min_quality_score'] ) {
            $errors[] = 'Quality score below minimum';
            $error_codes[] = 'quality_below_minimum';
        }
        if ( (float) ( $article['risk_score'] ?? 100 ) > (float) Plugin::settings()['max_risk_score'] ) {
            $errors[] = 'Risk score above maximum';
            $error_codes[] = 'risk_above_maximum';
        }
        if ( preg_match( '/fake review|guaranteed cure|5-star customer|limited time miracle|risk-free profit/i', $plain ) ) {
            $errors[] = 'Unsafe or deceptive claim detected';
            $error_codes[] = 'unsafe_claim';
        }
        if ( $this->keyword_stuffed( $plain ) ) {
            $errors[] = 'Keyword stuffing detected';
            $error_codes[] = 'keyword_stuffing';
        }

        return [
            'valid' => empty( $errors ),
            'errors' => $errors,
            'sanitized' => wp_kses_post( $body ),
            'article' => $article,
            'diagnostics' => $this->diagnostics( $article, $plain, $word_count, $min_words, $errors, $error_codes ),
        ];
    }

    public function word_count( string $plain ): int {
        $matched = preg_match_all( '/\p{L}[\p{L}\p{Mn}\p{Pd}\']*/u', $plain, $matches );
        if ( false !== $matched ) {
            return count( $matches[0] );
        }
        return str_word_count( $plain );
    }

    private function min_article_words(): int {
        return max( 100, absint( Plugin::settings()['min_article_words'] ?? 800 ) );
    }

    private function normalize_scores( array $article ): array {
        foreach ( [ 'quality_score', 'risk_score' ] as $field ) {
            if ( isset( $article[ $field ] ) && is_numeric( $article[ $field ] ) ) {
                $score = (float) $article[ $field ];
                if ( $score > 0 && $score <= 1 ) {
                    $score *= 100;
                }
                $article[ $field ] = max( 0, min( 100, $score ) );
            }
        }
        return $article;
    }

    private function diagnostics( array $article, string $plain, int $word_count, int $min_words, array $errors, array $error_codes ): array {
        $preview = function_exists( 'mb_substr' ) ? mb_substr( $plain, 0, 500 ) : substr( $plain, 0, 500 );
        $char_length = function_exists( 'mb_strlen' ) ? mb_strlen( $plain ) : strlen( $plain );

        return [
            'word_count' => $word_count,
            'min_article_words' => $min_words,
            'target_article_words' => absint( Plugin::settings()['target_article_words'] ?? 1400 ),
            'body_char_length' => $char_length,
            'section_count' => count( (array) ( $article['sections'] ?? [] ) ),
            'faq_count' => count( (array) ( $article['faq'] ?? [] ) ),
            'product_link_count' => count( (array) ( $article['product_mentions'] ?? $article['product_recommendations'] ?? [] ) ),
            'language' => sanitize_text_field( (string) ( Plugin::settings()['content_language'] ?? 'en' ) ),
            'validator_method' => 'unicode_preg_match_all',
            'error_codes' => array_values( array_unique( $error_codes ) ),
            'error_code' => $error_codes[0] ?? '',
            'title' => sanitize_text_field( (string) ( $article['seo_title'] ?? $article['title'] ?? '' ) ),
            'quality_score' => isset( $article['quality_score'] ) ? (float) $article['quality_score'] : null,
            'risk_score' => isset( $article['risk_score'] ) ? (float) $article['risk_score'] : null,
            'related_product_ids' => array_map( 'absint', (array) ( $article['related_product_ids'] ?? [] ) ),
            'body_preview' => sanitize_textarea_field( $preview ),
            'rejection_reasons' => array_values( $errors ),
        ];
    }

    private function keyword_stuffed( string $text ): bool {
        $words = [];
        if ( false === preg_match_all( '/\p{L}[\p{L}\p{Mn}\p{Pd}\']*/u', strtolower( $text ), $matches ) ) {
            $words = array_filter( preg_split( '/\W+/', strtolower( $text ) ) ?: [] );
        } else {
            $words = $matches[0];
        }
        $total = count( $words );
        if ( $total < 50 ) {
            return false;
        }
        $counts = array_count_values( $words );
        rsort( $counts );
        return ( (int) ( $counts[0] ?? 0 ) / $total ) > 0.08;
    }

    private function structured_body( array $article ): string {
        $parts = [ (string) ( $article['concise_answer'] ?? '' ), (string) ( $article['introduction'] ?? '' ) ];
        foreach ( (array) ( $article['sections'] ?? [] ) as $section ) {
            $parts[] = (string) ( $section['heading'] ?? '' );
            $parts[] = (string) ( $section['body'] ?? '' );
        }
        foreach ( (array) ( $article['faq'] ?? [] ) as $faq ) {
            $parts[] = (string) ( $faq['question'] ?? '' );
            $parts[] = (string) ( $faq['answer'] ?? '' );
        }
        foreach ( (array) ( $article['product_recommendations'] ?? [] ) as $recommendation ) {
            $parts[] = (string) ( $recommendation['anchor_text'] ?? '' );
            $parts[] = (string) ( $recommendation['reason'] ?? '' );
            $parts[] = (string) ( $recommendation['use_case'] ?? '' );
        }
        $parts[] = (string) ( $article['cta'] ?? '' );
        return implode( "\n\n", $parts );
    }

    private function full_article_text( array $article ): string {
        return trim( (string) ( $article['body'] ?? '' ) . "\n\n" . $this->structured_body( $article ) );
    }
}
