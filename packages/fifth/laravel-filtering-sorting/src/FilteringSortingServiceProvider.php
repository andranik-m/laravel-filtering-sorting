<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FilteringSortingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeFilterCommand::class,
                MakeSorterCommand::class,
            ]);
        }
    }

    public function register()
    {
        //
    }
}
