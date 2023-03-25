<?php

namespace Autepos\DiscountNkeLaravel;

use Autepos\Discount\Processors\LinearDiscountProcessor;
use Autepos\DiscountNkeLaravel\Contracts\DiscountProcessorFactory;
use Illuminate\Support\ServiceProvider;

class DiscountNkeLaravelServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Register linear discount manager
        $this->app->singleton(DiscountProcessorFactory::class, function ($app) {
            return new DiscountManager($app);
        });
    }

    /**
     * Boot he service provider
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Register default discount processor
         */
        $paymentManager = $this->app->make(DiscountProcessorFactory::class);

        $paymentManager->extend(LinearDiscountProcessor::PROCESSOR, function ($app) {
            return $app->make(LinearDiscountProcessor::class);
        });

        /**
         * Load and publish
         */
        if ($this->app->runningInConsole()) {
            //
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            //
            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'discount-nke-laravel-migrations');
        }
    }
}
