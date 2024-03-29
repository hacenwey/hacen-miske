<?php

namespace HacenMiske\CodeGenerator;

use Illuminate\Support\ServiceProvider;
use HacenMiske\CodeGenerator\Commands\CrudGenerator;

class CodeGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/stubs', 'CrudGenerator');

        $this->publishes([
            __DIR__ . '/resources/stubs' => resource_path('hacen-miske/stubs'),

        ]);
        $this->publishes([
            __DIR__ . '/config/crudSettings.json' => config_path('crudSettings.json'),

        ]);
    }



    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            CrudGenerator::class,
        ]);
    }
}
