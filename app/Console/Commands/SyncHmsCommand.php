<?php

namespace App\Console\Commands;

use App\Domain\Properties\Models\Property;
use App\Infrastructure\HMS\Sync\HmsSyncService;
use Illuminate\Console\Command;

class SyncHmsCommand extends Command
{
    protected $signature = 'hms:sync {property? : Property ID} {--entity= : Entity type to sync}';

    protected $description = 'Sync data from HMS for one or all properties';

    public function handle(HmsSyncService $syncService): int
    {
        $propertyId = $this->argument('property');
        $entity = $this->option('entity');

        $properties = $propertyId
            ? Property::where('id', $propertyId)->get()
            : Property::where('is_active', true)->get();

        if ($properties->isEmpty()) {
            $this->error('No properties found.');

            return self::FAILURE;
        }

        foreach ($properties as $property) {
            $this->info("Syncing property: {$property->name}");

            if ($entity) {
                $log = $syncService->syncEntity($property->id, $entity);
                $this->line("  {$entity}: {$log->status} ({$log->records_processed} processed)");
            } else {
                $logs = $syncService->syncAll($property->id);
                foreach ($logs as $log) {
                    $this->line("  {$log->entity_type}: {$log->status} ({$log->records_processed} processed)");
                }
            }
        }

        return self::SUCCESS;
    }
}
