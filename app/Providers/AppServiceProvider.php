<?php

namespace App\Providers;

use Astrocasts\Stocker\Infrastructure\Persistence\EventStore\CatalogEventStoreRepository;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Astrocasts\Stocker\Model\Catalog\Item;
use Illuminate\Support\ServiceProvider;
use Prooph\EventStore\Async\EventStoreConnection;
use Prooph\EventStore\EndPoint;
use Prooph\EventStore\UserCredentials;
use Prooph\EventStoreClient\ConnectionSettingsBuilder;
use Prooph\EventStoreClient\EventStoreConnectionFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app()->bind(Catalog::class, CatalogEventStoreRepository::class);
        app()->singleton(EventStoreConnection::class, function () {
            $builder = new ConnectionSettingsBuilder();
            //$builder->enableVerboseLogging();
            $builder->useConsoleLogger();
            $builder->setDefaultUserCredentials(new UserCredentials('admin', 'changeit'));

            return EventStoreConnectionFactory::createFromEndPoint(
                new EndPoint('127.0.0.1', 1113),
                $builder->build()
            );
        });

        app()->when(CatalogEventStoreRepository::class)
            ->needs('$streamCategory')
            ->give('catalog');
        app()->when(CatalogEventStoreRepository::class)
            ->needs('$aggregateRootClassName')
            ->give(Item::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
