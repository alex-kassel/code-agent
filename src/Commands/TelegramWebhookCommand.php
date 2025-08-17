<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent\Commands;

use AlexKassel\CodeAgent\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramWebhookCommand extends Command
{
    protected $signature = 'code-agent:webhook {--remove : Remove the webhook instead of setting it}';
    protected $description = 'Set or remove the Telegram webhook';

    public function __construct(
        protected TelegramService $telegramService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('remove')) {
            return $this->removeWebhook();
        }

        return $this->setWebhook();
    }

    private function setWebhook(): int
    {
        $webhookUrl = config('code-agent.telegram.webhook_url');

        if (empty($webhookUrl)) {
            $this->error('Webhook URL is not set. Please set TELEGRAM_WEBHOOK_URL in your .env file.');
            return self::FAILURE;
        }

        $this->info("Setting webhook to: {$webhookUrl}");

        if ($this->telegramService->setWebhook($webhookUrl)) {
            $this->info('Webhook set successfully!');
            return self::SUCCESS;
        }

        $this->error('Failed to set webhook.');
        return self::FAILURE;
    }

    private function removeWebhook(): int
    {
        $this->info('Removing webhook...');

        if ($this->telegramService->removeWebhook()) {
            $this->info('Webhook removed successfully!');
            return self::SUCCESS;
        }

        $this->error('Failed to remove webhook.');
        return self::FAILURE;
    }
}
