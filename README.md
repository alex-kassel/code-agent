# Code Agent

A Laravel 12 package that enables communication with an LLM via Telegram for code development, with the resulting code being pushed to GitHub.

## Features

- Communicate with an LLM (OpenAI) via Telegram
- Generate code based on prompts
- Automatically commit and push code to GitHub
- Easy installation and configuration

## Requirements

- PHP 8.2 or higher
- Laravel 12
- Telegram Bot Token
- OpenAI API Key
- GitHub Personal Access Token

## Installation

### 1. Add the package to your composer.json

```bash
composer require alex-kassel/code-agent
```

### 2. Run the installation command

```bash
php artisan code-agent:install
```

This will publish the configuration file and add the necessary environment variables to your `.env` file.

### 3. Configure your environment variables

Open your `.env` file and set the following variables:

```
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_WEBHOOK_URL=your-app-url/telegram/webhook
TELEGRAM_ALLOWED_USERS=username1,username2
LLM_API_KEY=your-openai-api-key
GITHUB_TOKEN=your-github-token
GITHUB_USERNAME=your-github-username
GITHUB_REPOSITORY=your-github-repository
```

### 4. Set up the Telegram webhook

```bash
php artisan code-agent:webhook
```

## Usage

1. Start a conversation with your Telegram bot
2. Send a message describing the code you want to generate
3. The bot will respond with the generated code
4. If the code should be committed to GitHub, the bot will format it with the special commit marker
5. The code will be automatically committed and pushed to your GitHub repository

## Example

User: "Create a simple Laravel controller for a blog post"

Bot:
```
Here's a simple BlogPostController:

```php
<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogPostController extends Controller
{
    public function index()
    {
        $posts = BlogPost::latest()->paginate(10);
        return view('blog.index', compact('posts'));
    }

    public function show(BlogPost $post)
    {
        return view('blog.show', compact('post'));
    }

    public function create()
    {
        return view('blog.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);

        $post = BlogPost::create($validated);

        return redirect()->route('blog.show', $post);
    }
}
```

COMMIT: Add BlogPostController for handling blog posts

Code committed: https://github.com/username/repository/commit/abc123
```

## Commands

- `php artisan code-agent:install` - Install the package
- `php artisan code-agent:webhook` - Set up the Telegram webhook
- `php artisan code-agent:webhook --remove` - Remove the Telegram webhook

## Configuration

You can customize the package behavior by modifying the `config/code-agent.php` file:

```php
return [
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'allowed_users' => explode(',', env('TELEGRAM_ALLOWED_USERS', '')),
    ],
    'llm' => [
        'provider' => env('LLM_PROVIDER', 'openai'),
        'api_key' => env('LLM_API_KEY'),
        'model' => env('LLM_MODEL', 'gpt-4o'),
        'temperature' => (float) env('LLM_TEMPERATURE', 0.7),
        'max_tokens' => (int) env('LLM_MAX_TOKENS', 4000),
    ],
    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'username' => env('GITHUB_USERNAME'),
        'repository' => env('GITHUB_REPOSITORY'),
        'branch' => env('GITHUB_BRANCH', 'main'),
    ],
];
```

## License

MIT
