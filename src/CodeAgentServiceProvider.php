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
                $telegramService = new TelegramService($config);

                // Inject LlmService if available
                if ($app->has(LlmService::class) && $app->make(LlmService::class) !== null) {
                    $telegramService->setLlmService($app->make(LlmService::class));
                }

                // Inject GithubService if available
                if ($app->has(GithubService::class) && $app->make(GithubService::class) !== null) {
                    $telegramService->setGithubService($app->make(GithubService::class));
                }

                return $telegramService;
            }

            // Return null if token is not available
            // This will prevent initialization errors when the service is not needed
            return null;
        });

        $this->app->singleton(LlmService::class, function ($app) {
            $config = config('code-agent.llm');

            // Only initialize if API key is available
            if (!empty($config['api_key'])) {
                try {
                    return new LlmService($config);
                } catch (\Exception $e) {
                    report($e);
                    return null;
                }
            }

            // Return null if API key is not available
            return null;
        });

        $this->app->singleton(GithubService::class, function ($app) {
            $config = config('code-agent.github');

            // Only initialize if token is available
            if (!empty($config['token'])) {
                try {
                    return new GithubService($config);
                } catch (\Exception $e) {
                    report($e);
                    return null;
                }
            }

            // Return null if token is not available
            return null;
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
