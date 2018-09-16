<?php

namespace FifthLLC\LaravelFilteringSorting;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as ElBuilder;
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

        Builder::macro('filterUsing', function (AbstractFilter $filter, string $method = 'handle', ...$args) {
            return $filter->{$method}($this, ...$args);
        });

        ElBuilder::macro('filterUsing', function (AbstractFilter $filter, string $method = 'handle', ...$args) {
            return $filter->{$method}($this, ...$args);
        });
    }

    public function register()
    {
        //
    }
}
