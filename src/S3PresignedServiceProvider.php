<?php

namespace Unisharp\S3\Presigned;

use Illuminate\Support\ServiceProvider;

class S3PresignedServiceProvider extends ServiceProvider
{
    /**
     * Boot the services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('s3_presigned', function ($app) {
            //
        });
    }

    /**
     * Boot configure.
     *
     * @return void
     */
    protected function bootConfig()
    {
        $path = __DIR__.'/config/captcha.php';
        $this->mergeConfigFrom($path, 's3_presigned');
        if (function_exists('config_path')) {
            $this->publishes([$path => config_path('s3_presigned.php')]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['s3_presigned'];
    }
}
