<?php
namespace OnKupon\Agent\Social\OAuth;

class XOAuthProvider {
    public function authorization_url(): string {
        $state = wp_generate_password( 24, false );
        set_transient( 'onkupon_agent_oauth_state_x', $state, 10 * MINUTE_IN_SECONDS );
        return add_query_arg( [ 'response_type' => 'code', 'client_id' => '', 'redirect_uri' => rest_url( 'onkupon-agent/v1/oauth/x/callback' ), 'state' => $state, 'scope' => 'tweet.read tweet.write users.read offline.access' ], 'https://twitter.com/i/oauth2/authorize' );
    }
}
