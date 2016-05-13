<?php

namespace Elodex\Console;

use Elodex\Contracts\IndexedModel as IndexedModelContract;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;

class Seed extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:seed
                            {model : Comma separated list of indexed model classes to seed}
                            {--s|save : Use the saving method (add or replace) which will not fail if a model already exists}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the index with models';

    /**
     * Array of progress bar formats.
     *
     * @var array
     */
    protected $progressBarFormats = [
        'normal' => "<info>%message%</info>\n %current%/%max% [%bar%] %percent:3s%%",
        'normal_nomax' => "<info>%message%</info>\n %current% [%bar%]",
        'verbose' => "<info>%message%</info>\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%",
        'verbose_nomax' => "<info>%message%</info>\n %current% [%bar%] %elapsed:6s%",
        'very_verbose' => "<info>%message%</info>\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%",
        'very_verbose_nomax' => "<info>%message%</info>\n %current% [%bar%] %elapsed:6s%",
        'debug' => "<info>%message%</info>\n %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%",
        'debug_nomax' => "<info>%message%</info>\n %current% [%bar%] %elapsed:6s% %memory:6s%",
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        $method = $this->option('save') ? 'save' : 'add';

        $this->initProgressBarFormats();

        $models = explode(',', trim($this->argument('model')));

        if (($models = $this->parseModelsArgument($models)) === false) {
            return 1;
        }

        foreach ($models as $model) {
            $this->addModelsToIndex($model, $method);
        }

        return 0;
    }

    /**
     * Checks if the specified models are valid indexed model classes and builds
     * an array of fully qualified class names.
     *
     * @param  array $models
     * @return bool|array
     */
    protected function parseModelsArgument(array $models)
    {
        $parsed = [];

        foreach ($models as $model) {
            $class = $this->parseModelName(trim($model));

            if (! class_exists($class)) {
                $this->error("Model class '{$class}' does not exist!");

                return false;
            }

            if (! ((new $class) instanceof IndexedModelContract)) {
                $this->error("Model class '{$class}' does not implement the '".IndexedModelContract::class."' interface!");

                return false;
            }

            $parsed[] = $class;
        }

        return $parsed;
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

        return $this->parseModelName(trim($rootNamespace, '\\').'\\'.$name);
    }

    /**
     * Init the ProgressBar formats.
     *
     * @return void
     */
    protected function initProgressBarFormats()
    {
        foreach ($this->progressBarFormats as $name => $format) {
            ProgressBar::setFormatDefinition($name, $format);
        }
    }

    /**
     * Add models of the specified type to the index.
     *
     * @param  string $class
     * @return void
     */
    protected function addModelsToIndex($class, $method = 'add', $chunkSize = 100)
    {
        $this->getOutput()->newLine(1);
        $progressBar = $this->getOutput()->createProgressBar();
        $progressBar->setMessage("Seeding '{$class}' models ...");

        $modelCount = $class::query()->getQuery()->getCountForPagination();
        $progressBar->start($modelCount);

        // Get the repository for the indexed model class.
        $repository = $class::getClassIndexRepository();

        // Use chunking to relax memory pressure.
        $class::chunk($chunkSize, function ($models) use ($repository, $method, $progressBar) {
            $repository->$method($models);

            $progressBar->advance($models->count());

            $models = null;
        });

        if ($modelCount === 0) {
            $this->warn("No instances of '{$class}' found, nothing to do.");
            $this->getOutput()->newLine(1);

            return;
        }

        $progressBar->finish();
        $this->getOutput()->newLine(1);

        $this->info("{$modelCount} instance(s) of '{$class}' added to index.");
        $this->getOutput()->newLine(1);
    }
}
