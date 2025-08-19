<?php

require_once __DIR__ . '/../../../../vendor/autoload.php';

use AlexKassel\CodeAgent\Services\GithubService;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../');
$dotenv->load();

// Configure GitHub service
$config = [
    'token' => env('GITHUB_TOKEN'),
    'username' => env('GITHUB_USERNAME'),
    'repository' => env('GITHUB_REPOSITORY'),
    'branch' => env('GITHUB_BRANCH', 'main'),
];

// Create a simple Laravel package with "hello laravel" functionality
$code = <<<'CODE'
{
    "name": "alex-kassel/hello-laravel",
    "description": "A simple Laravel package that says hello",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Alex Kassel",
            "email": "alex@example.com"
        }
    ],
    "require": {
        "php": "^8.0",
        "illuminate/support": "^8.0|^9.0|^10.0|^12.0"
    },
    "autoload": {
        "psr-4": {
            "AlexKassel\\HelloLaravel\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "AlexKassel\\HelloLaravel\\HelloLaravelServiceProvider"
            ],
            "aliases": {
                "HelloLaravel": "AlexKassel\\HelloLaravel\\Facades\\HelloLaravel"
            }
        }
    },
    "minimum-stability": "stable"
}

// Service Provider
<?php

namespace AlexKassel\\HelloLaravel;

use Illuminate\\Support\\ServiceProvider;

class HelloLaravelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('hello-laravel', function () {
            return new HelloLaravel();
        });
    }

    public function boot()
    {
        // Nothing to boot
    }
}

// Main class
<?php

namespace AlexKassel\\HelloLaravel;

class HelloLaravel
{
    public function sayHello()
    {
        return 'Hello Laravel!';
    }
}

// Facade
<?php

namespace AlexKassel\\HelloLaravel\\Facades;

use Illuminate\\Support\\Facades\\Facade;

class HelloLaravel extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'hello-laravel';
    }
}
CODE;

// Create GitHub service
try {
    $githubService = new GithubService($config);

    // Commit and push the code
    $commitUrl = $githubService->commitAndPush($code, "Add hello-laravel package composer.json");

    echo "Successfully pushed to GitHub: " . $commitUrl . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
