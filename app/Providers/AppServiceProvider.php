<?php

namespace App\Providers;

use Astrocasts\Stocker\Infrastructure\Persistence\EventStore\CatalogEventStoreRepository;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Illuminate\Support\ServiceProvider;
use Prooph\EventStoreClient\ConnectionSettingsBuilder;
use Prooph\EventStoreClient\EndPoint;
use Prooph\EventStoreClient\EventStoreConnection;
use Prooph\EventStoreClient\EventStoreConnectionFactory;
use Prooph\EventStoreClient\UserCredentials;

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
        app()->bind(EventStoreConnection::class, function () {
            return EventStoreConnectionFactory::createFromEndPoint(
                new EndPoint('localhost', 2113)
            );
        });

        app()->when(CatalogEventStoreRepository::class)
            ->needs('$streamCategory')
            ->give('catalog');
        app()->when(CatalogEventStoreRepository::class)
            ->needs('$aggregateRootClassName')
            ->give(Catalog::class);
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
