<?php
namespace OnKupon\Agent\Scheduler\Jobs;

class MetricsCollectionJob extends AbstractJob {
    protected function name(): string { return 'metrics_collection'; }
    protected function run(): void { ( new \OnKupon\Agent\Analytics\MetricsRepository() )->record( 'system', 0, 'internal', 'heartbeat', 1 ); }
}
