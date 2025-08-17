<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent\Services;

use AlexKassel\CodeAgent\Exceptions\TelegramException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramService
{
    protected Api $telegram;
    protected array $config;
    protected ?LlmService $llmService = null;
    protected ?GithubService $githubService = null;

    public function __construct(array $config)
    {
        $this->config = $config;

        try {
            $this->telegram = new Api($config['token']);
        } catch (TelegramSDKException $e) {
            throw new TelegramException('Failed to initialize Telegram API: ' . $e->getMessage());
        }
    }

    public function setLlmService(LlmService $llmService): self
    {
        $this->llmService = $llmService;
        return $this;
    }

    public function setGithubService(GithubService $githubService): self
    {
        $this->githubService = $githubService;
        return $this;
    }

    public function processUpdate(array $update): void
    {
        try {
            // Check if this is a message update
            if (!isset($update['message'])) {
                return;
            }

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $username = $message['from']['username'] ?? null;

            // Check if user is allowed
            if (!empty($this->config['allowed_users']) && !in_array($username, $this->config['allowed_users'])) {
                $this->sendMessage($chatId, 'You are not authorized to use this bot.');
                return;
            }

            // Process the message
            if (isset($message['text'])) {
                $this->processMessage($chatId, $message['text']);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Telegram update: ' . $e->getMessage());
        }
    }

    public function processMessage(int $chatId, string $text): void
    {
        // If LLM service is not set, return error message
        if (!$this->llmService) {
            $this->sendMessage($chatId, 'LLM service is not configured.');
            return;
        }

        // If GitHub service is not set, return error message
        if (!$this->githubService) {
            $this->sendMessage($chatId, 'GitHub service is not configured.');
            return;
        }

        // Send typing action
        $this->telegram->sendChatAction([
            'chat_id' => $chatId,
            'action' => 'typing',
        ]);

        try {
            // Get response from LLM
            $response = $this->llmService->generateResponse($text);

            // Check if response contains code to be committed
            if ($this->containsCodeToCommit($response)) {
                // Extract code and commit message
                [$code, $commitMessage] = $this->extractCodeAndCommitMessage($response);

                // Push to GitHub
                $commitUrl = $this->githubService->commitAndPush($code, $commitMessage);

                // Append commit URL to response
                $response .= "\n\nCode committed: $commitUrl";
            }

            // Send response back to user
            $this->sendMessage($chatId, $response);
        } catch (\Exception $e) {
            $this->sendMessage($chatId, 'Error: ' . $e->getMessage());
            Log::error('Error in Telegram message processing: ' . $e->getMessage());
        }
    }

    public function sendMessage(int $chatId, string $text): void
    {
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);
        } catch (TelegramSDKException $e) {
            Log::error('Failed to send Telegram message: ' . $e->getMessage());
        }
    }

    private function containsCodeToCommit(string $response): bool
    {
        // Check if response contains code blocks with commit markers
        return preg_match('/```.*?```.*?COMMIT:/s', $response) === 1;
    }

    private function extractCodeAndCommitMessage(string $response): array
    {
        // Extract code between code blocks
        preg_match('/```(.*?)```/s', $response, $codeMatches);
        $code = $codeMatches[1] ?? '';

        // Extract commit message after COMMIT: marker
        preg_match('/COMMIT:(.*?)$/s', $response, $commitMatches);
        $commitMessage = trim($commitMatches[1] ?? 'Update via Code Agent');

        return [$code, $commitMessage];
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = $this->telegram->setWebhook(['url' => $url]);
            return $response;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to set webhook: ' . $e->getMessage());
            return false;
        }
    }

    public function removeWebhook(): bool
    {
        try {
            $response = $this->telegram->removeWebhook();
            return $response;
        } catch (TelegramSDKException $e) {
            Log::error('Failed to remove webhook: ' . $e->getMessage());
            return false;
        }
    }
}
