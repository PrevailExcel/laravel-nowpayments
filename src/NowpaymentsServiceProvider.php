<?php

namespace PrevailExcel\Nowpayments;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/*
 * This file is part of the Laravel NOWPayments package.
 *
 * (c) Prevail Ejimadu <prevailexcellent@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class NowpaymentsServiceProvider extends ServiceProvider
{

    /*
    * Indicates if loading of the provider is deferred.
    *
    * @var bool
    */
    protected $defer = false;

    /**
    * Publishes all the config file this package needs to function
    */
    public function boot()
    {
        $config = realpath(__DIR__.'/../utils/config/nowpayments.php');

        $this->publishes([
            $config => config_path('nowpayments.php')
        ]);
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->mergeConfigFrom(
            __DIR__.'/../utils/config/nowpayments.php', 'nowpayments'
        );
        if (File::exists(__DIR__ . '/../utils/helpers/nowpayments.php')) {
            require __DIR__ . '/../utils/helpers/nowpayments.php';
        }
        $this->registerDashboard();


    }

    /**
    * Register the application services.
    */
    public function register()
    {
        $this->app->bind('laravel-nowpayments', function () {

            return new Nowpayments;

        });
    }

    
    /**
     * Register the dashboard components.
     *
     * @return void
     */
    protected function registerDashboard()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'nowpayments');
        $this->registerDashboardGate();
    }

    /**
     * Register the dashboard gate.
     *
     * @return void
     */
    protected function registerDashboardGate()
    {
        Gate::define('viewNowpaymentsDashboard', function ($user = null) {
            return $this->app->environment('local');
        });
    }


    /**
    * Get the services provided by the provider
    * @return array
    */
    public function provides()
    {
        return ['laravel-nowpayments'];
    }
}