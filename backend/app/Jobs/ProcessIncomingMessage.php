<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Messages\IncomingMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessIncomingMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $text,
        private readonly array $params = []
    ) {
    }

    public function handle(IncomingMessageService $service): void
    {
        $service->handle($this->text, $this->params);
    }
}
