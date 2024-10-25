<?php

namespace AutoDealersDigital\PhotoProcessor;

use Illuminate\Support\ServiceProvider;


class PhotoProcessorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/photo_processor.php', 'photo_processor'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/photo_processor.php' => config_path('photo_processor.php'),
        ]);
    }
}
