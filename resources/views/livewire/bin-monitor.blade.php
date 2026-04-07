<div wire:poll.5s>
    <div class="space-y-6">

        {{-- Threshold Control --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            <div class="flex items-center gap-2 mb-2 rounded-xl border border-green-200 dark:border-green-700 py-2 px-8 bg-green-50 dark:bg-green-900 space-y-2">
                <h2 for="fullThreshold" class="pt-1 mt-1.5 font-bold text-gray-700 dark:text-gray-300">
                    Configure Full Bin Threshold (%)
                </h2>
                <input type="number" id="fullThreshold" min="1" max="100" wire:model.defer="fullThreshold"
                    class="border border-gray-300 rounded px-4 py-1 mt-2 text-sm focus:outline-none focus:ring focus:border-blue-300">
                <button wire:click="saveThreshold"
                    class="mx-2 px-3 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
                    Save Threshold
                </button>
            </div>
            <div class="flex items-center gap-2 mb-2 rounded-xl border border-green-200 dark:border-green-700 py-2 px-8 bg-green-50 dark:bg-green-900 space-y-2">
                <a href="{{ route('bin_readings_export.pdf') }}" class="mx-2 mt-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Export Bin Readings to PDF
                </a>
                <a href="{{ route('bin_readings_export.csv') }}" class="mx-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Export Bin Readings to CSV
                </a>
            </div>
        </div>

        @if (session()->has('threshold_saved'))
            <div class="text-green-600 dark:text-green-400 text-sm mb-4">
                {{ session('threshold_saved') }}
            </div>
        @endif

        {{-- Cards for Biodegradable and Non-Biodegradable --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach (['bio' => 'Biodegradable', 'non-bio' => 'Non-Biodegradable'] as $type => $label)
                @php
                    $bin = $binsData->firstWhere('type', $type);
                    $fill = $bin['fill'] ?? null;
                @endphp

                <div class="p-4 md:col-span-2
                    @if ($fill >= $fullThreshold)
                        {{ 'bg-red-200' }}
                    @elseif ($fill >= $fullThreshold - 20 && $fill < $fullThreshold)
                        {{ 'bg-orange-200' }}
                    @elseif ($fill >= $fullThreshold - 40 && $fill < $fullThreshold - 20)
                        {{ 'bg-yellow-200' }}
                    @else
                        {{ 'bg-green-200' }}
                    @endif
                    dark:bg-gray-900 shadow rounded-xl">
                    <h2 class="text-lg font-semibold">{{ $label }} Bin</h2>
                    <div class="flex items-center space-x-2 mt-2">
                        <span class="text-2xl font-bold
                            @if ($fill >= $fullThreshold)
                                {{ 'text-red-500' }}
                            @elseif ($fill >= $fullThreshold - 20 && $fill < $fullThreshold)
                                {{ 'text-orange-500' }}
                            @elseif ($fill >= $fullThreshold - 40 && $fill < $fullThreshold - 20)
                                {{ 'text-yellow-400' }}
                            @else
                                {{ 'text-green-600' }}
                            @endif
                        ">{{ $fill ?? '-' }}%</span>
                        <span class="text-sm px-2 py-1 rounded
                            @if ($fill >= $fullThreshold)
                                {{ 'bg-red-500 text-white' }}
                            @elseif ($fill >= $fullThreshold - 20 && $fill < $fullThreshold)
                                {{ 'bg-orange-500' }}
                            @elseif ($fill >= $fullThreshold - 40 && $fill < $fullThreshold - 20)
                                {{ 'bg-yellow-400' }}
                            @else
                                {{ 'bg-green-600 text-white' }}
                            @endif
                        ">
                            {{ $bin['status'] ?? 'Unknown' }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Last updated: {{ $bin['updated_at'] ?? 'N/A' }}
                    </p>
                </div>
            @endforeach

            {{-- System Summary Cards --}}
            @foreach (['bio' => ['label' => 'Biodegradable', 'summary' => $bioSummary], 'non-bio' => ['label' => 'Non-Biodegradable', 'summary' => $nonbioSummary]] as $type => $data)
            <div class="p-4 bg-green-50 dark:bg-gray-900 shadow rounded-xl">
                <h2 class="text-lg font-semibold mb-2">{{ $data['label'] }} Summary</h2>
                <div class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                    <p><span class="font-medium">Last Full:</span> {{ $data['summary']['last_full_at'] }}</p>
                    <p><span class="font-medium">Last Emptied:</span> {{ $data['summary']['last_emptied_at'] }}</p>
                    <p><span class="font-medium">Time Until Emptied:</span> {{ $data['summary']['interval'] }}</p>
                </div>
            </div>
            @endforeach

            {{-- Legend Card --}}
            <div class="md:col-span-3 p-4 bg-green-50 dark:bg-gray-900 shadow rounded-xl">
                <h2 class="text-lg font-semibold">Status Legend</h2>
                <table class="min-w-full text-sm text-left">
                    <thead>
                        <tr>
                            <th class="py-2.5">
                                <span class="text-md text-white px-2 py-1 rounded bg-green-600">LOW</span>
                                <span class="text-md pl-2">Bin is empty/barely filled.</span>
                            </th>
                            <th class="py-2.5">
                                <span class="text-md px-2 py-1 rounded bg-yellow-400">HALF</span>
                                <span class="text-md pl-2">Bin is almost/exactly half-filled.</span>
                            </th>
                        </tr>
                        <tr>
                            <th class="py-2.5">
                                <span class="text-md px-2 py-1 rounded bg-orange-500">NEAR FULL</span>
                                <span class="text-md pl-2">Bin is filled by more than half.</span>
                            </th>
                            <th class="py-2.5">
                                <span class="text-md text-white px-2 py-1 rounded bg-red-600">FULL</span>
                                <span class="text-md pl-2">Bin is almost full; requires emptying.</span>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        {{-- Recent Readings --}}
        @foreach ([
            'bio'     => ['label' => 'Biodegradable',     'color' => 'green',  'readings' => $bioReadings,    'sortField' => $bioSortField,    'sortDir' => $bioSortDir,    'filterStatus' => $bioFilterStatus,    'perPage' => $bioPerPage,    'sortMethod' => 'sortBio',    'filterModel' => 'bioFilterStatus',    'perPageModel' => 'bioPerPage'],
            'non-bio' => ['label' => 'Non-Biodegradable', 'color' => 'cyan',   'readings' => $nonbioReadings, 'sortField' => $nonbioSortField, 'sortDir' => $nonbioSortDir, 'filterStatus' => $nonbioFilterStatus, 'perPage' => $nonbioPerPage, 'sortMethod' => 'sortNonBio', 'filterModel' => 'nonbioFilterStatus', 'perPageModel' => 'nonbioPerPage'],
        ] as $type => $t)
        <div class="bg-{{ $t['color'] }}-50 dark:bg-gray-900 shadow rounded-xl p-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="text-lg font-semibold">{{ $t['label'] }} Bin Readings</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model.live="{{ $t['filterModel'] }}"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white dark:bg-neutral-800 dark:text-gray-200 dark:border-neutral-600 focus:outline-none">
                        <option value="">All Statuses</option>
                        <option value="FULL">Full</option>
                        <option value="NEAR FULL">Near Full</option>
                        <option value="HALF">Half</option>
                        <option value="LOW">Low</option>
                    </select>
                    <select wire:model.live="{{ $t['perPageModel'] }}"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white dark:bg-neutral-800 dark:text-gray-200 dark:border-neutral-600 focus:outline-none">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-left divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-{{ $t['color'] }}-200 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 cursor-pointer select-none"
                                wire:click="{{ $t['sortMethod'] }}('created_at')">
                                Timestamp
                                @if($t['sortField'] === 'created_at') {{ $t['sortDir'] === 'asc' ? '↑' : '↓' }}
                                @else <span class="opacity-30">↕</span> @endif
                            </th>
                            <th class="px-4 py-2 cursor-pointer select-none"
                                wire:click="{{ $t['sortMethod'] }}('fill_level')">
                                Fill Level
                                @if($t['sortField'] === 'fill_level') {{ $t['sortDir'] === 'asc' ? '↑' : '↓' }}
                                @else <span class="opacity-30">↕</span> @endif
                            </th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @forelse ($t['readings'] as $reading)
                            @php $status = $this->determineStatus($reading->fill_level); @endphp
                            <tr class="hover:bg-{{ $t['color'] }}-100 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-2">{{ $reading->created_at->format('M d, Y H:i:s') }}</td>
                                <td class="px-4 py-2">{{ $reading->fill_level }}%</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-sm
                                        @if($status === 'FULL') bg-red-500 text-white
                                        @elseif($status === 'NEAR FULL') bg-orange-400
                                        @elseif($status === 'HALF') bg-yellow-400
                                        @else bg-green-500 text-white
                                        @endif">
                                        {{ $status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-gray-500">No readings found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination with first/last --}}
            <div class="mt-4 flex items-center justify-between gap-2 text-sm">
                <div class="flex gap-1">
                    <a href="{{ $t['readings']->url(1) }}"
                        class="px-2 py-1 rounded border {{ $t['readings']->onFirstPage() ? 'opacity-40 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-neutral-700' }}">
                        «
                    </a>
                    @foreach ($t['readings']->links()->elements[0] ?? [] as $page => $url)
                        <a href="{{ $url }}"
                            class="px-2 py-1 rounded border {{ $t['readings']->currentPage() === $page ? 'bg-' . $t['color'] . '-500 text-white' : 'hover:bg-gray-100 dark:hover:bg-neutral-700' }}">
                            {{ $page }}
                        </a>
                    @endforeach
                    <a href="{{ $t['readings']->url($t['readings']->lastPage()) }}"
                        class="px-2 py-1 rounded border {{ !$t['readings']->hasMorePages() ? 'opacity-40 pointer-events-none' : 'hover:bg-gray-100 dark:hover:bg-neutral-700' }}">
                        »
                    </a>
                </div>
                <span class="text-gray-500 dark:text-gray-400">
                    {{ $t['readings']->firstItem() }}–{{ $t['readings']->lastItem() }} of {{ $t['readings']->total() }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
