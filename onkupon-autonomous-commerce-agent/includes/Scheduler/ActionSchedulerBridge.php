<?php
namespace OnKupon\Agent\Scheduler;

class ActionSchedulerBridge {
    public function available(): bool {
        return function_exists( 'as_enqueue_async_action' );
    }

    public function enqueue( string $hook, array $args = [] ): void {
        if ( function_exists( 'as_enqueue_async_action' ) ) {
            as_enqueue_async_action( $hook, $args, JobRegistrar::GROUP );
            return;
        }
        if ( ! wp_next_scheduled( $hook, $args ) ) {
            wp_schedule_single_event( time() + 5, $hook, $args );
        }
    }
}
