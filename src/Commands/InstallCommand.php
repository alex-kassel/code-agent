<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'code-agent:install';
    protected $description = 'Install the Code Agent package';

    public function handle(): int
    {
        $this->info('Installing Code Agent...');

        // Publish the config file
        $this->call('vendor:publish', [
            '--tag' => 'code-agent-config',
        ]);

        // Add environment variables to .env file
        $this->addEnvironmentVariables();

        $this->info('Code Agent installed successfully!');
        $this->info('Please set the following environment variables in your .env file:');
        $this->info('TELEGRAM_BOT_TOKEN=your-telegram-bot-token');
        $this->info('TELEGRAM_WEBHOOK_URL=your-app-url/telegram/webhook');
        $this->info('TELEGRAM_ALLOWED_USERS=username1,username2');
        $this->info('LLM_API_KEY=your-openai-api-key');
        $this->info('GITHUB_TOKEN=your-github-token');
        $this->info('GITHUB_USERNAME=your-github-username');
        $this->info('GITHUB_REPOSITORY=your-github-repository');

        return self::SUCCESS;
    }

    private function addEnvironmentVariables(): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (File::exists($envPath)) {
            $envContents = File::get($envPath);
            $envExampleContents = File::exists($envExamplePath) ? File::get($envExamplePath) : '';

            $envVars = [
                'TELEGRAM_BOT_TOKEN' => '',
                'TELEGRAM_WEBHOOK_URL' => '',
                'TELEGRAM_ALLOWED_USERS' => '',
                'LLM_PROVIDER' => 'openai',
                'LLM_API_KEY' => '',
                'LLM_MODEL' => 'gpt-4o',
                'LLM_TEMPERATURE' => '0.7',
                'LLM_MAX_TOKENS' => '4000',
                'GITHUB_TOKEN' => '',
                'GITHUB_USERNAME' => '',
                'GITHUB_REPOSITORY' => '',
                'GITHUB_BRANCH' => 'main',
            ];

            foreach ($envVars as $key => $value) {
                if (!str_contains($envContents, $key . '=')) {
                    File::append($envPath, "\n{$key}={$value}");
                    $this->info("Added {$key} to .env file");
                }

                if (!str_contains($envExampleContents, $key . '=') && File::exists($envExamplePath)) {
                    File::append($envExamplePath, "\n{$key}={$value}");
                }
            }
        }
    }
}
