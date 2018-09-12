<?php

namespace App\Providers;


use Illuminate\Console\GeneratorCommand;

abstract class MakeCommand extends GeneratorCommand
{
    abstract protected function getStubName(): string;

    public function getStub()
    {
        //@TODO fix this
        return 'asd' . $this->getStubName();
    }

    protected function getClassName(): string
    {
        return $this->argument('name');
    }

    protected $replaceableVariables = [];

    public function handle()
    {
        $name = $this->qualifyClass($this->getClassName());

        $path = $this->getPath($name);

        $name = $this->qualifyClass($this->getNameInput());

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if ((! $this->hasOption('force') ||
                ! $this->option('force')) &&
            $this->alreadyExists($this->getNameInput())) {
            $this->error($this->type.' already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $this->files->put($path, $this->buildClass($name));

        $this->info($this->type.' created successfully.');
    }


    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        return $this->replaceReplaceableVariables($stub);
    }

    protected function workoutReplaceableVariables(): array
    {
        return $this->replaceableVariables;
    }

    /**
     * Replace the dummy variables for the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function replaceReplaceableVariables($stub): string
    {
        $replaceableVariables = $this->workoutReplaceableVariables();

        foreach ($replaceableVariables as $from => $to) {

            $stub = str_replace("{{" . $from . "}}", $to, $stub);
        }

        return $stub;
    }
}