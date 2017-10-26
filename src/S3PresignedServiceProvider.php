<?php

namespace Unisharp\S3\Presigned;

use Illuminate\Support\ServiceProvider;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Unisharp\S3\Presigned\S3Presigned;

class S3PresignedServiceProvider extends ServiceProvider
{
    protected $configs;

    /**
     * Boot the services for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootConfig();
        $this->loadConfig();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $configs = $this->configs;
        $credentials = new Credentials(
            $configs['credentials']['access_key'],
            $configs['credentials']['secret_key'],
        );
        $s3Client = new S3Client([
            'region'  => $configs['region'],
            'version' => $configs['version']
            'credentials' => $credentials,
            'options' => [
                $configs['options']
            ]
        ]);

        $this->app->singleton('s3.presigned', function ($app) use ($s3Client, $configs) {
            return new S3Presigned($s3Client, $configs['bucket'], $configs['prefix'], $configs)
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
     * Load configure.
     *
     * @return void
     */
    protected function loadConfig($configs = [])
    {
        $this->configs = $configs ?: config('s3_presigned');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['s3.presigned'];
    }
}
