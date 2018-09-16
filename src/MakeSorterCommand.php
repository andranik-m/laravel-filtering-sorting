<?php

namespace FifthLLC\LaravelFilteringSorting;


class MakeSorterCommand extends MakeCommand
{
    protected $name = 'make:sorter';

    protected $description = 'Make Sorter';

    protected $type = 'Sorter';

    protected function getStubName(): string
    {
        return 'sorter.stub';
    }
}