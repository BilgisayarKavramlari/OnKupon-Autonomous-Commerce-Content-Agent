<?php
namespace OnKupon\Agent\Security;

class SecretsManager {
    public function get( string $key ): string {
        $constant = 'ONKUPON_AGENT_' . strtoupper( $key );
        if ( defined( $constant ) ) {
            return (string) constant( $constant );
        }
        $env = getenv( $constant );
        if ( $env ) {
            return (string) $env;
        }
        $settings = \OnKupon\Agent\Plugin::settings();
        return (string) ( $settings[ strtolower( $key ) ] ?? '' );
    }

    public function mask( string $value ): string {
        return $value ? substr( $value, 0, 4 ) . '…' . substr( $value, -4 ) : '';
    }
}
