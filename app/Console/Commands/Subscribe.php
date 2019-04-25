<?php

namespace App\Console\Commands;

use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use Illuminate\Console\Command;
use Prooph\EventStore\EventStoreCatchUpSubscription;
use Prooph\EventStore\LiveProcessingStartedOnCatchUpSubscription;
use Prooph\EventStoreClient\CatchUpSubscriptionDropped;
use Prooph\EventStoreClient\CatchUpSubscriptionSettings;
use Prooph\EventStoreClient\EventAppearedOnCatchupSubscription;
use Prooph\EventStoreClient\EventStoreConnection;
use Prooph\EventStoreClient\ResolvedEvent;
use Prooph\EventStoreClient\SubscriptionDropReason;
use Prooph\EventStoreClient\UserCredentials;

class Subscribe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * @var EventStoreConnection
     */
    private $eventStoreConnection;

    public function __construct(
        EventStoreConnection $eventStoreConnection
    )
    {
        parent::__construct();
        $this->eventStoreConnection = $eventStoreConnection;
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Loop::run(function () {

            $connection = $this->eventStoreConnection;

            yield $connection->connectAsync();

            yield $connection->subscribeToAllFromAsync(
                null,
                CatchUpSubscriptionSettings::default(),
                new class() implements EventAppearedOnCatchupSubscription
                {
                    public function __invoke(
                        \Prooph\EventStoreClient\Internal\EventStoreCatchUpSubscription $subscription,
                        ResolvedEvent $resolvedEvent
                    ): Promise {
                        echo 'incoming event: ' . $resolvedEvent->originalEventNumber(
                            ) . '@' . $resolvedEvent->originalStreamName() . PHP_EOL;
                        echo 'data: ' . $resolvedEvent->originalEvent()->data() . PHP_EOL;
                        return new Success();
                    }
                },
                //new class() implements LiveProcessingStartedOnCatchUpSubscription
                //{
                //    public function __invoke(EventStoreCatchUpSubscription $subscription): void
                //    {
                //        echo 'liveProcessingStarted on ' . $subscription->streamId() . PHP_EOL;
                //    }
                //},
                null,
                new class() implements CatchUpSubscriptionDropped
                {
                    public function __invoke(
                        \Prooph\EventStoreClient\Internal\EventStoreCatchUpSubscription $subscription,
                        SubscriptionDropReason $reason,
                        ?\Throwable $exception = null
                    ): void {
                        echo 'dropped with reason: ' . $reason->name() . PHP_EOL;
                        if ($exception) {
                            echo 'ex: ' . $exception->getMessage() . PHP_EOL;
                        }
                    }
                },
                new UserCredentials('admin', 'changeit')
            );
        });

        return true;
    }
}
