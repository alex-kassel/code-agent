<?php

declare(strict_types=1);

namespace AlexKassel\CodeAgent\Http\Controllers;

use AlexKassel\CodeAgent\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function __construct(
        protected TelegramService $telegramService
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $update = $request->all();

            // Process the Telegram update
            $this->telegramService->processUpdate($update);

            return response()->noContent();
        } catch (\Exception $e) {
            report($e);
            return response()->noContent();
        }
    }
}
