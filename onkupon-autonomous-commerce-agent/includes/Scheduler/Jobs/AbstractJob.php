<?php
namespace OnKupon\Agent\Scheduler\Jobs;

use OnKupon\Agent\Logging\Logger;
use OnKupon\Agent\Plugin;
use OnKupon\Agent\Security\LockManager;

abstract class AbstractJob {
    abstract protected function name(): string;
    abstract protected function run(): void;

    public function handle(): void {
        $settings = Plugin::settings();
        if ( 'emergency_stopped' === ( $settings['agent_status'] ?? '' ) ) {
            return;
        }
        $lock = new LockManager();
        if ( ! $lock->acquire( $this->name(), 20 * MINUTE_IN_SECONDS ) ) {
            ( new Logger() )->log( 'warning', 'job', 'Skipped overlapping job', [ 'job' => $this->name() ] );
            return;
        }
        $logger = new Logger();
        $logger->start_run( $this->name() );
        try {
            $this->run();
            $logger->finish_run( $this->name(), 'completed' );
        } finally {
            $lock->release( $this->name() );
        }
    }
}
