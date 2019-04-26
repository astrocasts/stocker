<?php

namespace App\Console\Commands;

use Amp\Loop;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Illuminate\Console\Command;
use Prooph\EventStore\Async\EventStoreConnection;

class GetItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-item {item_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get item';


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

            /** @var Item $item */
            $item = yield $this->catalog->getItem(ItemId::fromString($this->argument('item_id')));

            if ($item) {
                $this->info('ItemId: ' . $item->aggregateId());
                $this->line('Name: ' . $item->name());
            } else {
                $this->warn('Item not found');
            }

            Loop::stop();
        });

        return true;
    }
}
