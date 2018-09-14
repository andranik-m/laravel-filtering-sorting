<?php

namespace App\Providers;


class MakeFilterCommand extends MakeCommand
{
    protected $name = 'make:filter';

    protected $description = 'Make Filter';

    protected $type = 'Filter';

    protected function getStubName(): string
    {
        return 'filter.stub';
    }
}