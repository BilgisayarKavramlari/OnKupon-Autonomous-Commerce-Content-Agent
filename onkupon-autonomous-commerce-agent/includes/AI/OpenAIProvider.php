<?php
namespace OnKupon\Agent\AI;

use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Logging\ActionTimelineRepository;
use OnKupon\Agent\Plugin;
use OnKupon\Agent\Security\RateLimiter;
use OnKupon\Agent\Security\SecretsManager;

class OpenAIProvider implements AIProviderInterface {
    private array $settings;

    public function __construct() {
        $this->settings = Plugin::settings();
    }

    public function generateJson( string $prompt, array $schema, array $options = [] ): array {
        $text = $this->request( $prompt, true, $options );
        $data = json_decode( $text, true );
        if ( ! is_array( $data ) ) {
            ( new Logger() )->log( 'warning', 'ai', 'OpenAI-compatible provider returned invalid JSON' );
            ( new ActionTimelineRepository() )->record( 'ai_invalid_json', 'failed', [ 'notes' => 'OpenAI-compatible provider returned invalid JSON', 'metadata' => [ 'model' => $this->settings['openai_model'] ?? '' ] ] );
            return [];
        }
        $validation = ( new JsonSchemaValidator() )->validate( $data, $schema );
        if ( ! $validation['valid'] ) {
            ( new Logger() )->log( 'warning', 'ai', 'Generated JSON failed schema validation', [ 'errors' => $validation['errors'] ] );
            ( new ActionTimelineRepository() )->record( 'ai_invalid_json', 'failed', [ 'notes' => implode( '; ', $validation['errors'] ), 'metadata' => [ 'model' => $this->settings['openai_model'] ?? '' ] ] );
            return [];
        }
        return $data;
    }

    public function generateText( string $prompt, array $options = [] ): string {
        return $this->request( $prompt, false, $options );
    }

    public function validateConnection(): bool {
        return '' !== $this->api_key() && wp_http_validate_url( $this->base_url() );
    }

    public function estimateCost( array $usage ): float {
        return ( absint( $usage['input_tokens'] ?? 0 ) * 0.00000015 ) + ( absint( $usage['output_tokens'] ?? 0 ) * 0.0000006 );
    }

    private function request( string $prompt, bool $json, array $options ): string {
        if ( ! $this->validateConnection() ) {
            return '';
        }
        if ( ! ( new RateLimiter() )->allow( 'openai', 120, HOUR_IN_SECONDS ) ) {
            ( new Logger() )->log( 'warning', 'ai', 'AI rate limit reached' );
            return '';
        }
        $body = [
            'model' => sanitize_text_field( $options['model'] ?? $this->settings['openai_model'] ),
            'messages' => [
                [ 'role' => 'system', 'content' => 'You are a safe commerce editorial assistant. Return factual, non-deceptive content. Never create customer reviews or fake ratings.' ],
                [ 'role' => 'user', 'content' => $prompt ],
            ],
            'temperature' => (float) $this->settings['openai_temperature'],
            'max_tokens' => absint( $this->settings['openai_max_tokens'] ),
        ];
        if ( $json ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }
        $response = wp_remote_post(
            trailingslashit( $this->base_url() ) . 'chat/completions',
            [
                'timeout' => absint( $this->settings['request_timeout'] ),
                'headers' => [ 'Authorization' => 'Bearer ' . $this->api_key(), 'Content-Type' => 'application/json' ],
                'body' => wp_json_encode( $body ),
            ]
        );
        if ( is_wp_error( $response ) ) {
            ( new Logger() )->log( 'error', 'ai', 'AI request failed', [ 'error' => $response->get_error_message() ] );
            return '';
        }
        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $decoded['usage'] ) ) {
            ( new Logger() )->cost( 'openai-compatible', (string) $body['model'], [ 'input_tokens' => $decoded['usage']['prompt_tokens'] ?? 0, 'output_tokens' => $decoded['usage']['completion_tokens'] ?? 0 ], $this->estimateCost( [ 'input_tokens' => $decoded['usage']['prompt_tokens'] ?? 0, 'output_tokens' => $decoded['usage']['completion_tokens'] ?? 0 ] ) );
        }
        return (string) ( $decoded['choices'][0]['message']['content'] ?? '' );
    }

    private function api_key(): string {
        return ( new SecretsManager() )->get( 'OPENAI_API_KEY' );
    }

    private function base_url(): string {
        return esc_url_raw( (string) $this->settings['openai_base_url'] );
    }
}
