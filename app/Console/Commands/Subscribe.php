<?php

namespace App\Console\Commands;

use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use Illuminate\Console\Command;
use Prooph\EventStore\Async\CatchUpSubscriptionDropped;
use Prooph\EventStore\Async\EventAppearedOnCatchupSubscription;
use Prooph\EventStore\Async\EventStoreCatchUpSubscription;
use Prooph\EventStore\Async\EventStoreConnection;
use Prooph\EventStore\Async\LiveProcessingStartedOnCatchUpSubscription;
use Prooph\EventStore\CatchUpSubscriptionSettings;
use Prooph\EventStore\Position;
use Prooph\EventStore\ResolvedEvent;
use Prooph\EventStore\SubscriptionDropReason;
use Prooph\EventStore\UserCredentials;

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
                //Position::parse('00000000069e594500000000069e5945'),
                Position::start(),
                CatchUpSubscriptionSettings::default(),
                new class() implements EventAppearedOnCatchupSubscription
                {
                    public function __invoke(
                        EventStoreCatchUpSubscription $subscription,
                        ResolvedEvent $resolvedEvent
                    ): Promise {
                        echo 'incoming event: ' . $resolvedEvent->originalEventNumber(
                            ) . '@' . $resolvedEvent->originalStreamName() . PHP_EOL;
                        echo 'Position: ' . $resolvedEvent->originalPosition()->asString()."\n";
                        //echo 'data: ' . $resolvedEvent->originalEvent()->data() . PHP_EOL;
                        return new Success();
                    }
                },
                new class() implements LiveProcessingStartedOnCatchUpSubscription
                {
                    public function __invoke(EventStoreCatchUpSubscription $subscription): void
                    {
                        echo 'liveProcessingStarted on ' . $subscription->streamId() . PHP_EOL;
                    }
                },
                new class() implements CatchUpSubscriptionDropped
                {
                    public function __invoke(
                        EventStoreCatchUpSubscription $subscription,
                        SubscriptionDropReason $reason,
                        ?\Throwable $exception = null
                    ): void {
                        echo 'dropped with reason: ' . $reason->name() . PHP_EOL;
                        if ($exception) {
                            echo 'ex: ' . $exception->getMessage() . PHP_EOL;
                        }
                    }
                }
            );
        });

        return true;
    }
}
