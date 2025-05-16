<?php

namespace Dwoodard\A2aLaravel;

use Dwoodard\A2aLaravel\Events\TaskStatusUpdated;
use Dwoodard\A2aLaravel\Listeners\PushNotificationListener;
use Dwoodard\A2aLaravel\Services\AgentService;
use Dwoodard\A2aLaravel\Services\SkillRegistry;
use Dwoodard\A2aLaravel\Services\TaskManager;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class A2aLaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/a2a.php', 'a2a');
        $this->app->singleton(AgentService::class);
        $this->app->singleton(TaskManager::class);
        $this->app->singleton(SkillRegistry::class, function ($app) {
            return new SkillRegistry(config('a2a.skills', []));
        });
    }

    public function boot()
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Individual tags
        $this->publishes([
            __DIR__.'/../config/a2a.php' => config_path('a2a.php'),
        ], 'a2a-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'a2a-migrations');

        // Combined "a2a" tag
        $this->publishes([
            __DIR__.'/../config/a2a.php' => config_path('a2a.php'),
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'a2a');

        // Register routes
        $this->registerRoutes();

        \Illuminate\Support\Facades\Event::listen(
            TaskStatusUpdated::class,
            [PushNotificationListener::class, 'handle']
        );

    }

    protected function registerRoutes()
    {
        Route::middleware('web')
            ->group(realpath(__DIR__.'/../routes/a2a.php'));
    }
}
