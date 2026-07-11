<?php

use App\Domain\Shared\Models\ExternalMapping;
use App\Domain\Shared\Models\SyncLog;
use App\Infrastructure\HMS\Sync\HmsSyncService;
use Livewire\Volt\Component;

new class extends Component
{
    public function syncLogs()
    {
        return SyncLog::with('property')->latest()->limit(20)->get();
    }

    public function mappingCounts()
    {
        return ExternalMapping::selectRaw('entity_type, count(*) as count')
            ->groupBy('entity_type')
            ->pluck('count', 'entity_type');
    }

    public function runSync(HmsSyncService $syncService): void
    {
        $this->authorize('sync.run');
        $propertyId = app(\App\Domain\Properties\Services\PropertyContext::class)->id();
        $syncService->syncAll($propertyId);
        session()->flash('message', 'Sync completed successfully.');
    }

    public function runEntitySync(string $entity, HmsSyncService $syncService): void
    {
        $this->authorize('sync.run');
        $propertyId = app(\App\Domain\Properties\Services\PropertyContext::class)->id();
        $syncService->syncEntity($propertyId, $entity);
    }
}; ?>

<div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">HMS Sync Status</h1>
            @can('sync.run')
                <button wire:click="runSync" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700">Sync All</button>
            @endcan
        </div>

        @if (session('message'))
            <div class="bg-green-50 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('message') }}</div>
        @endif

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ($this->mappingCounts() as $type => $count)
                <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
                    <div class="text-sm text-slate-500 capitalize">{{ str_replace('_', ' ', $type) }}</div>
                    <div class="text-2xl font-bold text-slate-900">{{ $count }}</div>
                    @can('sync.run')
                        <button wire:click="runEntitySync('{{ $type === 'guest' ? 'guests' : ($type === 'reservation' ? 'reservations' : ($type === 'fnb_order' ? 'fnb_orders' : 'event_bookings')) }}')" class="text-xs text-indigo-600 mt-1">Sync</button>
                    @endcan
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100">
            <div class="p-4 border-b border-slate-100 font-semibold">Recent Sync Logs</div>
            <table class="w-full text-sm">
                <thead class="bg-slate-50"><tr>
                    <th class="text-left px-4 py-2">Entity</th>
                    <th class="text-left px-4 py-2">Status</th>
                    <th class="text-left px-4 py-2">Processed</th>
                    <th class="text-left px-4 py-2">Created</th>
                    <th class="text-left px-4 py-2">Updated</th>
                    <th class="text-left px-4 py-2">Time</th>
                </tr></thead>
                <tbody>
                    @forelse ($this->syncLogs() as $log)
                        <tr class="border-t border-slate-50">
                            <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $log->entity_type) }}</td>
                            <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-xs {{ $log->status === 'completed' ? 'bg-green-100 text-green-800' : ($log->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">{{ $log->status }}</span></td>
                            <td class="px-4 py-2">{{ $log->records_processed }}</td>
                            <td class="px-4 py-2">{{ $log->records_created }}</td>
                            <td class="px-4 py-2">{{ $log->records_updated }}</td>
                            <td class="px-4 py-2 text-slate-500">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No sync logs yet. Run a sync to populate data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
