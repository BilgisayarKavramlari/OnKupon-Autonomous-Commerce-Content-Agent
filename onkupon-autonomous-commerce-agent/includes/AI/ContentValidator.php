<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Plugin;

class ContentValidator {
    public function validate( array $article ): array {
        $article = $this->normalize_scores( $article );
        $schema_result = ( new JsonSchemaValidator() )->validate( $article, ( new PromptBuilder() )->article_schema() );
        $errors = $schema_result['errors'];
        $body = (string) ( $article['body'] ?? '' );
        $plain = wp_strip_all_tags( $body );
        $word_count = $this->word_count( $plain );
        $min_words = $this->min_article_words();

        if ( $word_count < $min_words ) {
            $errors[] = 'Content is too thin';
        }
        if ( (float) ( $article['quality_score'] ?? 0 ) < (float) Plugin::settings()['min_quality_score'] ) {
            $errors[] = 'Quality score below minimum';
        }
        if ( (float) ( $article['risk_score'] ?? 100 ) > (float) Plugin::settings()['max_risk_score'] ) {
            $errors[] = 'Risk score above maximum';
        }
        if ( preg_match( '/fake review|guaranteed cure|5-star customer|limited time miracle|risk-free profit/i', $plain ) ) {
            $errors[] = 'Unsafe or deceptive claim detected';
        }
        if ( $this->keyword_stuffed( $plain ) ) {
            $errors[] = 'Keyword stuffing detected';
        }

        return [
            'valid' => empty( $errors ),
            'errors' => $errors,
            'sanitized' => wp_kses_post( $body ),
            'article' => $article,
            'diagnostics' => $this->diagnostics( $article, $plain, $word_count, $min_words, $errors ),
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
        return max( 50, absint( Plugin::settings()['min_article_words'] ?? 250 ) );
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

    private function diagnostics( array $article, string $plain, int $word_count, int $min_words, array $errors ): array {
        $preview = function_exists( 'mb_substr' ) ? mb_substr( $plain, 0, 500 ) : substr( $plain, 0, 500 );
        $char_length = function_exists( 'mb_strlen' ) ? mb_strlen( $plain ) : strlen( $plain );

        return [
            'word_count' => $word_count,
            'min_article_words' => $min_words,
            'body_char_length' => $char_length,
            'language' => sanitize_text_field( (string) ( Plugin::settings()['content_language'] ?? 'en' ) ),
            'validator_method' => 'unicode_preg_match_all',
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
}
