<?php

namespace App\Infrastructure\HMS\Sync\Jobs;

use App\Infrastructure\HMS\Sync\HmsSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncHmsEntityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $propertyId,
        public string $entityType,
    ) {}

    public function handle(HmsSyncService $syncService): void
    {
        $syncService->syncEntity($this->propertyId, $this->entityType);
    }
}
