<?php

namespace Qkktrip\AliyunSMS;

use Illuminate\Support\ServiceProvider;

class AliyunSMSServiceProvider extends ServiceProvider
{

    /**
     * Boot the service provider.
     *
     * @return null
     */
    public function boot()
    {
        $source = realpath(__DIR__ . '/config.php');

        // Publish configuration files
        $this->publishes([
            $source => config_path('aliyunsms.php')
        ]);

    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Merge configs
        $this->mergeConfigFrom(
            config_path('aliyunsms.php'), 'aliyunsms'
        );

        $this->app->singleton('aliyunsms', function ($app) {
            return new AliyunSMS(config('aliyunsms'));
        });
    }

}
