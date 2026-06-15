<?php
namespace OnKupon\Agent\Scheduler;

class JobRegistrar {
    public const GROUP = 'onkupon-agent';

    public function register(): void {
        foreach ( self::hooks() as $hook => $class ) {
            add_action( $hook, [ new $class(), 'handle' ] );
        }
    }

    public static function hooks(): array {
        return [
            'onkupon_agent_product_scan' => \OnKupon\Agent\Scheduler\Jobs\ProductScanJob::class,
            'onkupon_agent_research'     => \OnKupon\Agent\Scheduler\Jobs\ResearchJob::class,
            'onkupon_agent_content'      => \OnKupon\Agent\Scheduler\Jobs\ContentGenerationJob::class,
            'onkupon_agent_publish'      => \OnKupon\Agent\Scheduler\Jobs\PublishingJob::class,
            'onkupon_agent_social'       => \OnKupon\Agent\Scheduler\Jobs\SocialPublishingJob::class,
            'onkupon_agent_metrics'      => \OnKupon\Agent\Scheduler\Jobs\MetricsCollectionJob::class,
            'onkupon_agent_learning'     => \OnKupon\Agent\Scheduler\Jobs\LearningJob::class,
            'onkupon_agent_reviews'      => \OnKupon\Agent\Scheduler\Jobs\ReviewRequestJob::class,
            'onkupon_agent_cleanup'      => \OnKupon\Agent\Scheduler\Jobs\CleanupJob::class,
        ];
    }

    public static function schedule_defaults(): void {
        if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
            return;
        }
        $intervals = [
            'onkupon_agent_product_scan' => 6 * HOUR_IN_SECONDS,
            'onkupon_agent_research'     => 2 * HOUR_IN_SECONDS,
            'onkupon_agent_content'      => 4 * HOUR_IN_SECONDS,
            'onkupon_agent_publish'      => HOUR_IN_SECONDS,
            'onkupon_agent_social'       => 15 * MINUTE_IN_SECONDS,
            'onkupon_agent_metrics'      => 6 * HOUR_IN_SECONDS,
            'onkupon_agent_learning'     => DAY_IN_SECONDS,
            'onkupon_agent_reviews'      => DAY_IN_SECONDS,
            'onkupon_agent_cleanup'      => WEEK_IN_SECONDS,
        ];
        foreach ( $intervals as $hook => $interval ) {
            if ( ! as_next_scheduled_action( $hook, [], self::GROUP ) ) {
                as_schedule_recurring_action( time() + MINUTE_IN_SECONDS, $interval, $hook, [], self::GROUP );
            }
        }
    }

    public static function unschedule_all(): void {
        if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
            return;
        }
        foreach ( array_keys( self::hooks() ) as $hook ) {
            as_unschedule_all_actions( $hook, [], self::GROUP );
        }
    }
}
