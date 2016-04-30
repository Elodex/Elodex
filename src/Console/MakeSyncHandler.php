<?php

namespace Elodex\Console;

use Elodex\Contracts\IndexedModel as IndexedModelContract;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeSyncHandler extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:es:sync-handler
                            {name : The indexed model class}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new index synchronization handler for an indexed Elodex model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Listener';

    /**
     * The postfix used for the sync handler.
     *
     * @var string
     */
    protected $listenerPostfix = 'IndexSyncHandler';

    /**
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $model = $this->parseModelName($this->argument('name'));

        // Check if the model class exists.
        if (! class_exists($model)) {
            $this->error("Model class '{$model}' does not exist!");

            return false;
        }

        // Check if the model class implements the indexed model interface.
        if (! ((new $model) instanceof IndexedModelContract)) {
            $this->error("Model class '{$model}' does not implement the '".IndexedModelContract::class."' interface!");

            return false;
        }

        parent::fire();
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $fqModelClass = $this->parseModelName($this->argument('name'));

        $stub = parent::buildClass($name);

        $stub = str_replace(
            '{FQModelClass}', $fqModelClass, $stub
        );

        $stub = str_replace(
            '{Listener}', class_basename($name), $stub
        );

        return $stub;
    }

    /**
     * Get the listener class name for the 'name' argument.
     *
     * @param  string $name
     * @return string
     */
    protected function getListenerClass($name)
    {
        return class_basename($name) . $this->listenerPostfix;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/listener.stub';
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($this->parseName($rawName));
    }

    /**
     * Parse the model name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function parseModelName($name)
    {
        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        if (Str::contains($name, '/')) {
            $name = str_replace('/', '\\', $name);
        }

        return $this->parseName(trim($rootNamespace, '\\').'\\'.$name);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Listeners';
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return $this->getListenerClass(trim($this->argument('name')));
    }
}
