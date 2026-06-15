<?php
namespace OnKupon\Agent\Admin;

class ContentTimelinePage extends BasePage {
    public function render(): void {
        $rows = array_map(
            static function ( $r ) {
                $metadata = json_decode( (string) ( $r['metadata_json'] ?? '' ), true );
                $metadata = is_array( $metadata ) ? $metadata : [];
                return [
                    esc_html( $r['created_at'] ?? '' ),
                    esc_html( $r['status'] ?? '' ),
                    esc_html( $r['action_type'] ?? '' ),
                    esc_html( (string) ( $r['object_id'] ?? '' ) ),
                    esc_html( wp_trim_words( $r['error_message'] ?? '', 12 ) ),
                    esc_html( (string) ( $metadata['word_count'] ?? '' ) ),
                    esc_html( (string) ( $metadata['quality_score'] ?? '' ) ),
                    esc_html( (string) ( $metadata['risk_score'] ?? '' ) ),
                    esc_html( wp_trim_words( $metadata['body_preview'] ?? '', 30 ) ),
                ];
            },
            $this->recent_rows( 'onkupon_agent_actions' )
        );
        $this->header( __( 'Content Timeline', 'onkupon-agent' ) );
        $this->table( [ 'Date', 'Status', 'Action', 'Object ID', 'Notes', 'Word count', 'Quality score', 'Risk score', 'Preview' ], $rows );
        $this->footer();
    }
}
