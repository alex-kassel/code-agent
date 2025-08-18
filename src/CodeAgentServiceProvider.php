<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent;

use AlexKassel\CodeAgent\Commands\InstallCommand;
use AlexKassel\CodeAgent\Commands\TelegramWebhookCommand;
use AlexKassel\CodeAgent\Services\GithubService;
use AlexKassel\CodeAgent\Services\LlmService;
use AlexKassel\CodeAgent\Services\TelegramService;
use Illuminate\Support\ServiceProvider;

class CodeAgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/code-agent.php', 'code-agent'
        );

        $this->app->singleton(TelegramService::class, function ($app) {
            $config = config('code-agent.telegram');

            // Only initialize if token is available
            if (!empty($config['token'])) {
                return new TelegramService($config);
            }

            // Return null if token is not available
            // This will prevent initialization errors when the service is not needed
            return null;
        });

        $this->app->singleton(LlmService::class, function ($app) {
            return new LlmService(config('code-agent.llm'));
        });

        $this->app->singleton(GithubService::class, function ($app) {
            return new GithubService(config('code-agent.github'));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/code-agent.php' => config_path('code-agent.php'),
            ], 'code-agent-config');

            $this->commands([
                InstallCommand::class,
                TelegramWebhookCommand::class,
            ]);
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
