<div wire:poll.10s>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        {{-- Stats Cards --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="p-4 bg-neutral-200 rounded-xl border border-neutral-700">
                <h4 class="text-sm font-medium text-gray-600">Total Logs</h4>
                <p class="mt-2 text-2xl font-semibold text-gray-800">{{ $total }}</p>
            </div>
            <div class="p-4 bg-green-100 rounded-xl border border-neutral-700">
                <h4 class="text-sm font-medium text-gray-600">Success</h4>
                <p class="mt-2 text-2xl font-semibold text-green-600">{{ $success }}</p>
            </div>
            <div class="p-4 bg-yellow-100 rounded-xl border border-neutral-700">
                <h4 class="text-sm font-medium text-gray-600">Warnings</h4>
                <p class="mt-2 text-2xl font-semibold text-yellow-600">{{ $warning }}</p>
            </div>
        </div>

        {{-- Console Log Area --}}
        <div class="rounded-xl border border-neutral-700 bg-neutral-900 p-4 shadow">

            {{-- Console Header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="text-sm font-mono text-gray-400">Robotic Arm Log Console</div>
                    <div class="text-xs font-mono text-gray-500">{{ $total }} entries</div>
                </div>
                <div class="flex items-center gap-2">
                    <select wire:model.live="filterStatus"
                        class="text-sm font-mono border border-neutral-600 rounded-lg px-3 py-1.5 bg-neutral-800 text-gray-200 focus:outline-none">
                        <option value="">All</option>
                        <option value="SUCCESS">SUCCESS</option>
                        <option value="WARNING">WARNING</option>
                    </select>
                    <select wire:model.live="perPage"
                        class="text-sm font-mono border border-neutral-600 rounded-lg px-3 py-1.5 bg-neutral-800 text-gray-200 focus:outline-none">
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                        <option value="200">200 rows</option>
                    </select>
                </div>
            </div>

            {{-- Log Entries --}}
            <div class="font-mono text-sm bg-black rounded-lg p-4 min-h-96 space-y-1 overflow-y-auto">
                @forelse ($logs as $log)
                    <div class="flex gap-2">
                        <span class="text-gray-500 shrink-0">
                            [{{ $log->performed_at->format('Y-m-d H:i:s') }}]
                        </span>
                        <span @class([
                            'shrink-0',
                            'text-green-400' => $log->status === 'SUCCESS',
                            'text-yellow-400' => $log->status === 'WARNING',
                        ])>
                            [{{ $log->status }}]
                        </span>
                        <span class="text-gray-300">
                            {{ $log->description }}
                        </span>
                        @if ($log->wasteObject)
                            <span class="text-gray-500 shrink-0">
                                — #{{ $log->waste_object_id }} ({{ $log->wasteObject->classification }})
                            </span>
                        @endif
                    </div>
                @empty
                    <div class="text-gray-500">[System] No logs found.</div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="mt-3 text-sm font-mono text-gray-500">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
