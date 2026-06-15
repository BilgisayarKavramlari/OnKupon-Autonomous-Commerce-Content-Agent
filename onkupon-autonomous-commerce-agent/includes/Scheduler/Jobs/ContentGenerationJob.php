<?php
namespace OnKupon\Agent\Scheduler\Jobs;

class ContentGenerationJob extends AbstractJob {
    protected function name(): string { return 'content_generation'; }
    protected function run(): void {
        if ( ! \OnKupon\Agent\Plugin::can_publish() ) { return; }
        $article = ( new \OnKupon\Agent\AI\ContentGenerator() )->generate_next();
        if ( $article ) { ( new \OnKupon\Agent\Publishing\WordPressPublisher() )->publish( $article ); }
    }
}
