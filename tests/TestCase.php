<?php

namespace Autepos\DiscountNkeLaravel\Tests;

use Autepos\DiscountNkeLaravel\Contracts\DiscountProcessorFactory;
use Autepos\DiscountNkeLaravel\DiscountManager;
use Autepos\DiscountNkeLaravel\DiscountNkeLaravelServiceProvider;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.connections.mysql.engine', 'InnoDB');
    }

    /**
     * Get discount manager instance.
     */
    protected function discountManager(): DiscountManager
    {
        return app(DiscountProcessorFactory::class);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            DiscountNkeLaravelServiceProvider::class,
            \Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class,
        ];
    }
}
