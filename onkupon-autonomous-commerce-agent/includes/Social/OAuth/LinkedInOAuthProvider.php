<?php
namespace OnKupon\Agent\Social\OAuth;

class LinkedInOAuthProvider {
    public function authorization_url(): string {
        $state = wp_generate_password( 24, false );
        set_transient( 'onkupon_agent_oauth_state_linkedin', $state, 10 * MINUTE_IN_SECONDS );
        return add_query_arg( [ 'response_type' => 'code', 'client_id' => '', 'redirect_uri' => rest_url( 'onkupon-agent/v1/oauth/linkedin/callback' ), 'state' => $state, 'scope' => 'w_member_social w_organization_social' ], 'https://www.linkedin.com/oauth/v2/authorization' );
    }
}
