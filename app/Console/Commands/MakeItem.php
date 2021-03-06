<?php

namespace App\Console\Commands;

use Amp\Loop;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\Model\Catalog\Name;
use Illuminate\Console\Command;
use Prooph\EventStore\Async\EventStoreConnection;

class MakeItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:make-item {name}';

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

            yield $connection->connectAsync();

            $itemId = ItemId::generate();
            $name = Name::fromString($this->argument('name'));
            $item = Item::create($itemId, $name);

            $this->info('ItemId: ' . $itemId);

            yield $this->catalog->saveItem($item);

            $this->info('Saved.');

            Loop::stop();
        });

        return true;
    }
}
