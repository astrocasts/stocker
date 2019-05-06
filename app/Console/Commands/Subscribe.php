<?php

namespace App\Console\Commands;

use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use App\CatalogItem;
use Astrocasts\Stocker\EventSourcing\MessageTransformer;
use Astrocasts\Stocker\Model\Catalog\Events\ItemCreated;
use Astrocasts\Stocker\Model\Catalog\Events\ItemRenamed;
use Illuminate\Console\Command;
use Prooph\EventStore\Async\CatchUpSubscriptionDropped;
use Prooph\EventStore\Async\EventAppearedOnCatchupSubscription;
use Prooph\EventStore\Async\EventStoreCatchUpSubscription;
use Prooph\EventStore\Async\EventStoreConnection;
use Prooph\EventStore\Async\LiveProcessingStartedOnCatchUpSubscription;
use Prooph\EventStore\CatchUpSubscriptionSettings;
use Prooph\EventStore\Internal\Consts;
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
    protected $description = 'Subscribe to Event Store events';


    /**
     * @var EventStoreConnection
     */
    private $eventStoreConnection;

    /**
     * @var MessageTransformer
     */
    private $messageTransformer;

    public function __construct(
        EventStoreConnection $eventStoreConnection,
        MessageTransformer $messageTransformer
    )
    {
        parent::__construct();
        $this->eventStoreConnection = $eventStoreConnection;
        $this->messageTransformer = $messageTransformer;
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



            yield $connection->subscribeToStreamFromAsync(
            //yield $connection->subscribeToAllFromAsync(
                //Position::parse('00000000132466810000000013246681'),
                '$ce-catalog',
                //Position::start()->commitPosition(),
                0,
                new CatchUpSubscriptionSettings(
                    Consts::CATCH_UP_DEFAULT_MAX_PUSH_QUEUE_SIZE / 2,
                    Consts::CATCH_UP_DEFAULT_READ_BATCH_SIZE / 2,
                    false,
                    true
                ),
                new class() implements EventAppearedOnCatchupSubscription
                {
                    public function __invoke(
                        EventStoreCatchUpSubscription $subscription,
                        ResolvedEvent $resolvedEvent
                    ): Promise {
                        //$actualEventType = MessageTransformer::resolveFromContractName(
                        //    $resolvedEvent->event()->eventType()
                        //);

                        switch ($resolvedEvent->event()->eventType()) {
                            case 'Astrocasts.Stocker.Model.Catalog.Events.ItemCreated':
                                try {
                                    $json = json_decode($resolvedEvent->event()->data(), true);
                                    $catalogItem = new CatalogItem();
                                    $catalogItem->id = $json['itemId'];
                                    $catalogItem->name = $json['name'];
                                    $catalogItem->save();
                                } catch (\Exception $e) {
                                    print " [ oops creating item ]\n";
                                }

                                break;

                            case 'Astrocasts.Stocker.Model.Catalog.Events.ItemRenamed':
                                $json = json_decode($resolvedEvent->event()->data(), true);
                                $catalogItem = CatalogItem::firstOrCreate([
                                    'id' => $json['itemId'],
                                ]);
                                $catalogItem->name = $json['name'];
                                $catalogItem->save();

                                break;
                        }

                        /*
                        echo 'incoming event: ' . $resolvedEvent->originalEventNumber(
                            ) . '@' . $resolvedEvent->originalStreamName() . PHP_EOL;
                        //print_r($resolvedEvent->originalPosition());

                        print_r($resolvedEvent->event()->data());
                        */
                        //echo 'Position: ' . $resolvedEvent->originalPosition()->asString()."\n";
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
