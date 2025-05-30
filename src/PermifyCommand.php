<?php

namespace Permify;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'permify')]
class PermifyCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'permify
                    { type : The preset type (bootstrap, vue, react, tailwind) }
                    { --auth : Install authentication Permify UI scaffolding }
                    { --option=* : Pass an option to the preset command }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Swap the front-end scaffolding for the application';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function handle()
    {
        if (static::hasMacro($this->argument('type'))) {
            return call_user_func(static::$macros[$this->argument('type')], $this);
        }

        if (! in_array($this->argument('type'), ['bootstrap', 'vue', 'react', 'tailwind'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        if ($this->option('auth')) {
            $this->call('permify:auth');
        }

        $this->{$this->argument('type')}();
    }

    /**
     * Install the "bootstrap" preset.
     *
     * @return void
     */
    protected function bootstrap()
    {
        Presets\Bootstrap::install();

        $this->components->info('Bootstrap scaffolding installed successfully.');
        $this->components->warn('Please run [npm install && npm run dev] to compile your fresh scaffolding.');
    }

    /**
     * Install the "tailwind" preset.
     *
     * @return void
     */
    protected function tailwind()
    {
        Presets\Tailwind::essentialInstall();
        Presets\Tailwind::install();

        $this->components->info('Tailwind scaffolding installed successfully.');
        $this->components->warn('Please run [npm install && npm run dev] to compile your fresh scaffolding.');
    }

    /**
     * Install the "vue" preset.
     *
     * @return void
     */
    protected function vue()
    {
        Presets\Bootstrap::install();
        Presets\Vue::install();

        $this->components->info('Vue scaffolding installed successfully.');
        $this->components->warn('Please run [npm install && npm run dev] to compile your fresh scaffolding.');
    }

    /**
     * Install the "react" preset.
     *
     * @return void
     */
    protected function react()
    {
        Presets\Bootstrap::install();
        Presets\React::install();

        $this->components->info('React scaffolding installed successfully.');
        $this->components->warn('Please run [npm install && npm run dev] to compile your fresh scaffolding.');
    }
}
