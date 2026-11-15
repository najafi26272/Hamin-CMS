<?php

namespace Hamin\FormBuilder;

use Hamin\FormBuilder\Console\InstallCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FormBuilderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/form-builder.php', 'form-builder');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'form-builder');

        $this->publishes([__DIR__.'/../config/form-builder.php' => config_path('form-builder.php')], 'form-builder-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/form-builder')], 'form-builder-views');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }

        $this->registerLivewireComponents();
        $this->registerRoutes();
    }

    private function registerLivewireComponents(): void
    {
        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component('form-builder.admin.forms', Livewire\Admin\FormsIndex::class);
        \Livewire\Livewire::component('form-builder.admin.editor', Livewire\Admin\FormEditor::class);
        \Livewire\Livewire::component('form-builder.admin.submissions', Livewire\Admin\Submissions::class);
        \Livewire\Livewire::component('form-builder.form', Livewire\FormRenderer::class);
    }

    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $config = config('form-builder');
        $livewire = class_exists(\Livewire\Livewire::class);

        // پنل مدیریت
        if ($livewire && $config['admin']['enabled']) {
            $middleware = $config['admin']['middleware'];
            if ($config['admin']['gate']) {
                $middleware[] = 'can:'.$config['admin']['gate'];
            }

            Route::middleware($middleware)->prefix($config['admin']['prefix'])->name('form-builder.admin.')
                ->group(__DIR__.'/../routes/admin.php');
        }

        // صفحه عمومی فرم
        if ($livewire && $config['public']['enabled']) {
            Route::middleware($config['public']['middleware'])->prefix($config['public']['prefix'])->name('form-builder.')
                ->group(__DIR__.'/../routes/public.php');
        }

        // API
        if ($config['api']['enabled']) {
            Route::middleware($config['api']['middleware'])->prefix($config['api']['prefix'])->name('form-builder.api.')
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
