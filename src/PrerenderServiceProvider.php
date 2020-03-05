<?php

namespace Swift1337\Prerender;

use Illuminate\Support\ServiceProvider;
use Swift1337\Prerender\Prerender\Prerenderer;
use function config;

class PrerenderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/prerender.php', 'prerender');
    }

    public function boot(): void
    {
        // publish config
        $this->publishes(
            [
                __DIR__ . '/config/prerender.php' => config_path('prerender.php'),
            ],
            'laravel-prerender'
        );

        // bind service
        $this->app->bind(Prerenderer::class, function () {
            return new Prerenderer(config('prerender'));
        });
    }
}
