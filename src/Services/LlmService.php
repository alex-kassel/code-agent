<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent\Services;

use AlexKassel\CodeAgent\Exceptions\LlmException;
use Illuminate\Support\Facades\Log;
use OpenAI;
use OpenAI\Client;

class LlmService
{
    protected Client $client;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        try {
            $this->client = OpenAI::client($config['api_key']);
        } catch (\Exception $e) {
            throw new LlmException('Failed to initialize LLM client: ' . $e->getMessage());
        }
    }

    public function generateResponse(string $prompt): string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => $this->config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $this->config['temperature'],
                'max_tokens' => $this->config['max_tokens'],
            ]);

            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::error('LLM error: ' . $e->getMessage());
            throw new LlmException('Failed to generate response: ' . $e->getMessage());
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<EOT
You are a helpful AI assistant specialized in software development.
You can help with coding, debugging, and providing explanations about code.

When you provide code that should be committed to GitHub, format your response like this:

```language
// Your code here
```

COMMIT: Your commit message here

This format will allow the system to automatically commit the code to GitHub.
EOT;
    }
}
