<?php
namespace OnKupon\Agent\Admin;

class ContentTimelinePage extends BasePage {
    public function render(): void {
        $rows = array_map( static fn( $r ) => [ $r['created_at'] ?? '', $r['status'] ?? '', $r['action_type'] ?? '', $r['object_id'] ?? '', esc_html( wp_trim_words( $r['error_message'] ?? '', 12 ) ) ], $this->recent_rows( 'onkupon_agent_actions' ) );
        $this->header( __( 'Content Timeline', 'onkupon-agent' ) );
        $this->table( [ 'Date', 'Status', 'Action', 'Object ID', 'Notes' ], $rows );
        $this->footer();
    }
}
