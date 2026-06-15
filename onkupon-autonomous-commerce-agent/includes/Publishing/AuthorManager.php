<?php
namespace OnKupon\Agent\Publishing;

use OnKupon\Agent\Plugin;

class AuthorManager {
    public function author_id(): int {
        $configured = absint( Plugin::settings()['default_author_id'] ?? 0 );
        if ( $configured && get_user_by( 'id', $configured ) ) {
            return $configured;
        }
        $current = get_current_user_id();
        if ( $current ) {
            return $current;
        }
        $admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ] );
        return absint( $admins[0] ?? 1 );
    }
}
