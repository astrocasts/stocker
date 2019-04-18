<?php

namespace App\Console\Commands;

use Amp\Loop;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\Model\Catalog\Name;
use Illuminate\Console\Command;
use Prooph\EventStoreClient\EventStoreConnection;
use Prooph\EventStoreClient\Position;
use Prooph\EventStoreClient\UserCredentials;

class MakeItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make an item';
    /** @var Catalog */

    private $catalog;

    /**
     * @var EventStoreConnection
     */
    private $eventStoreConnection;

    public function __construct(
        Catalog $catalog,
        EventStoreConnection $eventStoreConnection
    )
    {
        parent::__construct();
        $this->catalog = $catalog;
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

            $connection->onConnected(function (): void {
                echo 'connected' . PHP_EOL;
            });
            $connection->onClosed(function (): void {
                echo 'connection closed' . PHP_EOL;
            });

            $connection->connectAsync()->onResolve(function ($error = null, $result = null) use ($connection) {
                if ($error) {
                    echo 'Could not connect... ' . $error->getMessage(). "\n";
                } else {
                    echo 'Connected!' . "\n";

                    try {
                        $ae = yield $connection->readAllEventsForwardAsync(Position::start(), 2, false, new UserCredentials(
                            'admin',
                            'changeit'
                        ));
                    } catch (\Throwable $e) {
                        print "{{" . $e->getMessage(). "}}\n";
                    }

                    print " stuff \n";

                    print " [ $ae ]\n";

                }
            });


            //sleep(5);
            $itemId = ItemId::generate();
            $name = Name::fromString('elephpant');
            $item = Item::create($itemId, $name);

            $this->catalog->saveItem($item)->onResolve(function (?\Throwable $error = null, $result = null) {
                if ($error) {
                    echo "asyncOperation1 fail -> " . $error->getMessage() . PHP_EOL;
                } else {
                    echo "asyncOperation1 result -> " . $result . PHP_EOL;
                }
                /**
                jobFail()->onResolve(function (Throwable $error = null, $result = null) {
                if ($error) {
                echo "asyncOperation2 fail -> " . $error->getMessage() . PHP_EOL;
                } else {
                echo "asyncOperation2 result -> " . $result . PHP_EOL;
                }
                });
                 * */
            });

            $this->comment('Done?');
        });


        return true;
    }
}
