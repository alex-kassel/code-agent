<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent\Services;

use AlexKassel\CodeAgent\Exceptions\GithubException;
use Github\Client;
use Github\Exception\RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GithubService
{
    protected Client $client;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        try {
            $this->client = new Client();
            $this->client->authenticate($config['token'], null, Client::AUTH_ACCESS_TOKEN);
        } catch (\Exception $e) {
            throw new GithubException('Failed to initialize GitHub client: ' . $e->getMessage());
        }
    }

    public function commitAndPush(string $code, string $commitMessage): string
    {
        try {
            $username = $this->config['username'];
            $repository = $this->config['repository'];
            $branch = $this->config['branch'];

            // Generate a unique filename based on the commit message
            $filename = $this->generateFilename($commitMessage);

            // Create a new blob with the file content
            $blob = $this->client->git()->blobs()->create($username, $repository, [
                'content' => $code,
                'encoding' => 'utf-8'
            ]);

            try {
                // Try to get the current reference - this will fail if the repository is empty
                $reference = $this->client->git()->references()->show($username, $repository, "heads/{$branch}");
                $latestCommitSha = $reference['object']['sha'];

                // Get the base tree
                $baseTree = $this->client->git()->trees()->show($username, $repository, $latestCommitSha);

                // Create a new tree with the new file
                $tree = $this->client->git()->trees()->create($username, $repository, [
                    'base_tree' => $baseTree['sha'],
                    'tree' => [
                        [
                            'path' => $filename,
                            'mode' => '100644',
                            'type' => 'blob',
                            'sha' => $blob['sha']
                        ]
                    ]
                ]);

                // Create a new commit
                $commit = $this->client->git()->commits()->create($username, $repository, [
                    'message' => $commitMessage,
                    'tree' => $tree['sha'],
                    'parents' => [$latestCommitSha]
                ]);

                // Update the reference
                $this->client->git()->references()->update($username, $repository, "heads/{$branch}", [
                    'sha' => $commit['sha'],
                    'force' => false
                ]);
            } catch (RuntimeException $e) {
                // If we get here, the repository might be empty or the branch doesn't exist
                // Create a tree without a base tree (for empty repos)
                $tree = $this->client->git()->trees()->create($username, $repository, [
                    'tree' => [
                        [
                            'path' => $filename,
                            'mode' => '100644',
                            'type' => 'blob',
                            'sha' => $blob['sha']
                        ]
                    ]
                ]);

                // Create a new commit without parents (initial commit)
                $commit = $this->client->git()->commits()->create($username, $repository, [
                    'message' => $commitMessage,
                    'tree' => $tree['sha'],
                    'parents' => []
                ]);

                try {
                    // Try to update the reference if it exists
                    $this->client->git()->references()->update($username, $repository, "heads/{$branch}", [
                        'sha' => $commit['sha'],
                        'force' => true
                    ]);
                } catch (RuntimeException $refException) {
                    // If the reference doesn't exist, create it
                    $this->client->git()->references()->create($username, $repository, [
                        'ref' => "refs/heads/{$branch}",
                        'sha' => $commit['sha']
                    ]);
                }
            }

            // Return the URL to the commit
            return "https://github.com/{$username}/{$repository}/commit/{$commit['sha']}";
        } catch (RuntimeException $e) {
            Log::error('GitHub error: ' . $e->getMessage());
            throw new GithubException('Failed to commit and push: ' . $e->getMessage());
        }
    }

    private function generateFilename(string $commitMessage): string
    {
        // Generate a filename based on the commit message
        $slug = Str::slug(Str::limit($commitMessage, 40, ''));
        $timestamp = now()->format('YmdHis');
        $extension = $this->detectFileExtension($commitMessage);

        return "code-agent/{$timestamp}-{$slug}.{$extension}";
    }

    private function detectFileExtension(string $commitMessage): string
    {
        // Try to detect the file extension from the commit message
        $extensions = [
            'php' => ['php', 'laravel', 'symfony'],
            'js' => ['javascript', 'js', 'node', 'vue', 'react'],
            'py' => ['python', 'py', 'django', 'flask'],
            'rb' => ['ruby', 'rails'],
            'java' => ['java', 'spring'],
            'go' => ['golang', 'go'],
            'ts' => ['typescript', 'ts'],
            'html' => ['html', 'template'],
            'css' => ['css', 'style', 'stylesheet'],
            'json' => ['json', 'config'],
            'md' => ['markdown', 'md', 'documentation'],
        ];

        $commitLower = strtolower($commitMessage);

        foreach ($extensions as $ext => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($commitLower, $keyword) !== false) {
                    return $ext;
                }
            }
        }

        // Default to txt if no match
        return 'txt';
    }
}
