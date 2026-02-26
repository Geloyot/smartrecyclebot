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

            {{-- System Summary Card --}}
            <div class="p-4 bg-green-50 dark:bg-gray-900 shadow rounded-xl">
                <h2 class="text-lg font-semibold">System Summary</h2>
                <p class="mt-2">Full bins: {{ $fullBinCount }}</p>
                <p>Next check: {{ $nextCheckTime }}</p>
            </div>

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
        <div class="flex flex-col md:flex-row gap-6">
            {{-- Biodegradable Bin Table --}}
            <div class="flex-1 bg-green-50 dark:bg-gray-900 shadow rounded-xl p-4">
                <h2 class="text-lg font-semibold mb-4">Biodegradable Bin Readings</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-green-200 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2">Timestamp</th>
                                <th class="px-4 py-2">Fill Level</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (collect($recentReadings)->where('bin_type', 'bio') as $reading)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $reading['timestamp'] }}</td>
                                    <td class="px-4 py-2">{{ $reading['fill_level'] }}%</td>
                                    <td class="px-4 py-2">{{ $reading['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-center text-gray-500">No biodegradable readings found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Non-Biodegradable Bin Table --}}
            <div class="flex-1 bg-cyan-50 dark:bg-gray-900 shadow rounded-xl p-4">
                <h2 class="text-lg font-semibold mb-4">Non-Biodegradable Bin Readings</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-cyan-200 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-2">Timestamp</th>
                                <th class="px-4 py-2">Fill Level</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (collect($recentReadings)->where('bin_type', 'non-bio') as $reading)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-2">{{ $reading['timestamp'] }}</td>
                                    <td class="px-4 py-2">{{ $reading['fill_level'] }}%</td>
                                    <td class="px-4 py-2">{{ $reading['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-2 text-center text-gray-500">No non-biodegradable readings found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
