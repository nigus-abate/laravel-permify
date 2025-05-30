<?php

namespace Permify;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'permify:auth')]
class AuthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = 'permify:auth
    //                 { type=bootstrap : The preset type (bootstrap) }
    //                 {--views : Only scaffold the authentication views}
    //                 {--force : Overwrite existing views by default}';
    protected $signature = 'permify:auth
                    { type=bootstrap : The preset type (bootstrap, tailwind) }
                    {--views : Only scaffold the authentication views}
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic login and registration views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.stub' => 'auth/login.blade.php',
        'auth/passwords/confirm.stub' => 'auth/passwords/confirm.blade.php',
        'auth/passwords/email.stub' => 'auth/passwords/email.blade.php',
        'auth/passwords/reset.stub' => 'auth/passwords/reset.blade.php',
        'auth/register.stub' => 'auth/register.blade.php',
        'auth/verify.stub' => 'auth/verify.blade.php',
        'home.stub' => 'home.blade.php',
        'layouts/app.stub' => 'layouts/app.blade.php',
        'layouts/admin.stub' => 'layouts/admin.blade.php',
        // New for role permission and users
        'admin/roles/index.stub' => 'admin/roles/index.blade.php',
        'admin/roles/form.stub' => 'admin/roles/form.blade.php',
        'admin/permissions/index.stub' => 'admin/permissions/index.blade.php',
        'admin/permissions/form.stub' => 'admin/permissions/form.blade.php',
        'admin/users/index.stub' => 'admin/users/index.blade.php',
        'admin/users/form.stub' => 'admin/users/form.blade.php',
        'admin/dashboard/index.stub' => 'admin/dashboard/index.blade.php',

    ];

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

        if (! in_array($this->argument('type'), ['bootstrap', 'tailwind'])) {
            throw new InvalidArgumentException('Invalid preset.');
        }

        $this->ensureDirectoriesExist();
        $this->exportViews();

        if (! $this->option('views')) {
            $this->exportBackend();
        }

        $this->components->info('Authentication scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function ensureDirectoriesExist()
    {
        if (! is_dir($directory = $this->getViewPath('layouts'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getViewPath('auth/passwords'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getViewPath('admin/roles'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getViewPath('admin/permissions'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getViewPath('admin/users'))) {
            mkdir($directory, 0755, true);
        }

        if (! is_dir($directory = $this->getViewPath('admin/dashboard'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            if (file_exists($view = $this->getViewPath($value)) && ! $this->option('force')) {
                if (! $this->components->confirm("The [$value] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            copy(
                __DIR__.'/Auth/'.$this->argument('type').'-stubs/'.$key,
                $view
            );
        }
    }

    /**
     * Export the authentication backend.
     *
     * @return void
     */
    protected function exportBackend()
    {
        $this->callSilent('permify:controllers');

        $controller = app_path('Http/Controllers/HomeController.php');

        if (file_exists($controller) && ! $this->option('force')) {
            if ($this->components->confirm("The [HomeController.php] file already exists. Do you want to replace it?", true)) {
                file_put_contents($controller, $this->compileStub('controllers/HomeController'));
            }
        } else {
            file_put_contents($controller, $this->compileStub('controllers/HomeController'));
        }

        $baseController = app_path('Http/Controllers/Controller.php');

        if (file_exists($baseController) && ! $this->option('force')) {
            if ($this->components->confirm("The [Controller.php] file already exists. Do you want to replace it?", true)) {
                file_put_contents($baseController, $this->compileStub('controllers/Controller'));
            }
        } else {
            file_put_contents($baseController, $this->compileStub('controllers/Controller'));
        }

        if (! file_exists(database_path('migrations/0001_01_01_000000_create_users_table.php'))) {
            copy(
                __DIR__.'/../stubs/migrations/2014_10_12_100000_create_password_resets_table.php',
                base_path('database/migrations/2014_10_12_100000_create_password_resets_table.php')
            );
        }

        // new for role and for role and permissions
        if (! file_exists(database_path('migrations/2025_05_24_000000_create_roles_and_permissions_tables.php'))) {
            copy(
                __DIR__.'/../stubs/migrations/2025_05_24_000000_create_roles_and_permissions_tables.php',
                base_path('database/migrations/2025_05_24_000000_create_roles_and_permissions_tables.php')
            );
        }

        file_put_contents(
            base_path('routes/web.php'),
            file_get_contents(__DIR__.'/Auth/stubs/routes.stub'),
            FILE_APPEND
        );
    }

    /**
     * Compiles the given stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function compileStub($stub)
    {
        return str_replace(
            '{{namespace}}',
            $this->laravel->getNamespace(),
            file_get_contents(__DIR__.'/Auth/stubs/'.$stub.'.stub')
        );
    }

    /**
     * Get full view path relative to the application's configured view path.
     *
     * @param  string  $path
     * @return string
     */
    protected function getViewPath($path)
    {
        return implode(DIRECTORY_SEPARATOR, [
            config('view.paths')[0] ?? resource_path('views'), $path,
        ]);
    }
}
