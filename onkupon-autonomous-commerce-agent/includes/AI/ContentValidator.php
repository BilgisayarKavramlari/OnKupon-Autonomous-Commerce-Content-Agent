<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Plugin;

class ContentValidator {
    public function validate( array $article ): array {
        $schema_result = ( new JsonSchemaValidator() )->validate( $article, ( new PromptBuilder() )->article_schema() );
        $errors = $schema_result['errors'];
        $body = (string) ( $article['body'] ?? '' );
        $plain = wp_strip_all_tags( $body );

        if ( str_word_count( $plain ) < 250 ) {
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
        ];
    }

    private function keyword_stuffed( string $text ): bool {
        $words = array_filter( preg_split( '/\W+/', strtolower( $text ) ) ?: [] );
        $total = count( $words );
        if ( $total < 50 ) {
            return false;
        }
        $counts = array_count_values( $words );
        rsort( $counts );
        return ( (int) ( $counts[0] ?? 0 ) / $total ) > 0.08;
    }
}
