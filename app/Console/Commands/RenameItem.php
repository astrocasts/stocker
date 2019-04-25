<?php

namespace App\Console\Commands;


use Amp\Loop;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\Model\Catalog\Name;
use Illuminate\Console\Command;
use Prooph\EventStoreClient\EventStoreConnection;

class RenameItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rename-item {item_id} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rename an item';
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

            $itemId = ItemId::fromString($this->argument('item_id'));
            $name = Name::fromString($this->argument('name'));

            /** @var Item $item */
            $item = yield $this->catalog->getItem($itemId);

            $this->info('ItemId: ' . $itemId);
            $this->info('Old name: ' . $item->name());
            $this->info('New name: ' . $name);

            $item->rename($name);

            yield $this->catalog->saveItem($item);

            $this->info('Saved.');

            Loop::stop();
        });

        return true;
    }
}
