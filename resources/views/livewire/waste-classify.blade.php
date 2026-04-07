<div wire:poll.30s>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Threshold Control Section --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-2 mb-6">
            <div class="flex items-center gap-2 rounded-xl border border-indigo-200 dark:border-indigo-700 py-2 px-8 bg-indigo-50 dark:bg-indigo-900">
                <h2 class="pt-1 mt-1.5 font-bold text-gray-700 dark:text-gray-300">
                    Configure Accuracy Threshold (%)
                </h2>
                <input type="number" id="accuracyThreshold" min="1" max="100" wire:model.defer="accuracyThreshold"
                    class="border border-gray-300 rounded px-4 py-1 mt-2 text-sm focus:outline-none focus:ring focus:border-blue-300">
                <button wire:click="saveThreshold"
                    class="mx-2 px-3 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                    Save Threshold
                </button>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-indigo-200 dark:border-indigo-700 py-2 px-8 bg-indigo-50 dark:bg-indigo-900">
                <a href="{{ route('classifications_export.pdf') }}" class="mx-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Export Classifications to PDF
                </a>
                <a href="{{ route('classifications_export.csv') }}" class="mx-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Export Classifications to CSV
                </a>
            </div>
        </div>

        @if (session()->has('threshold_saved'))
            <div class="text-green-600 dark:text-green-400 text-sm mb-4">
                {{ session('threshold_saved') }}
            </div>
        @endif
        {{-- Card section row --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">

            {{-- Total Classifications --}}
            <div class="rounded-xl border border-neutral-200 bg-yellow-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-md text-bold text-gray-700 dark:text-gray-600 mb-1">Classifications</div>
                <div class="text-2xl font-semibold text-gray-900 dark:text-white mb-3">{{ $stats['total_count'] }}</div>
                <div class="flex gap-2">
                    @foreach(['today' => 'Today', 'week' => 'This Week', 'overall' => 'Overall'] as $val => $label)
                        <button wire:click="setFilter('total', '{{ $val }}')"
                            class="text-s text-bold px-4 py-1 rounded-full border transition cursor-pointer
                                {{ $filterTotal === $val ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-cyan-50 dark:bg-neutral-800 dark:text-gray-300 dark:border-neutral-600' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Biodegradable --}}
            <div class="rounded-xl border border-neutral-200 bg-green-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-md text-bold text-gray-700 dark:text-gray-600 mb-1">Biodegradable</div>
                <div class="text-2xl font-semibold text-green-600 dark:text-green-400 mb-3">{{ $stats['biodegradable'] }}</div>
                <div class="flex gap-2">
                    @foreach(['today' => 'Today', 'week' => 'This Week', 'overall' => 'Overall'] as $val => $label)
                        <button wire:click="setFilter('bio', '{{ $val }}')"
                            class="text-s text-bold px-4 py-1 rounded-full border transition cursor-pointer
                                {{ $filterBio === $val ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-green-50 dark:bg-neutral-800 dark:text-gray-300 dark:border-neutral-600' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Non-Biodegradable --}}
            <div class="rounded-xl border border-neutral-200 bg-blue-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-md text-bold text-gray-700 dark:text-gray-600 mb-1">Non-Biodegradable</div>
                <div class="text-2xl font-semibold text-cyan-600 dark:text-cyan-400 mb-3">{{ $stats['non_biodegradable'] }}</div>
                <div class="flex gap-2">
                    @foreach(['today' => 'Today', 'week' => 'This Week', 'overall' => 'Overall'] as $val => $label)
                        <button wire:click="setFilter('nonbio', '{{ $val }}')"
                            class="text-s text-bold px-4 py-1 rounded-full border transition cursor-pointer
                                {{ $filterNonBio === $val ? 'bg-cyan-600 text-white border-cyan-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-cyan-50 dark:bg-neutral-800 dark:text-gray-300 dark:border-neutral-600' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Table section --}}
        <div class="relative flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-yellow-50 shadow dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                <div class="text-lg font-semibold text-gray-800 dark:text-white">Recent Classifications</div>

                <div class="flex flex-wrap items-center gap-2">
                    <select wire:model.live="filterLabel"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white dark:bg-neutral-800 dark:text-gray-200 dark:border-neutral-600 focus:outline-none focus:ring focus:border-blue-300">
                        <option value="">All Labels</option>
                        <option value="Biodegradable">Biodegradable</option>
                        <option value="Non-Biodegradable">Non-Biodegradable</option>
                    </select>

                    <select wire:model.live="filterScore"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white dark:bg-neutral-800 dark:text-gray-200 dark:border-neutral-600 focus:outline-none focus:ring focus:border-blue-300">
                        <option value="">All Scores</option>
                        <option value="HIGH">High</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="LOW">Low</option>
                    </select>

                    <select wire:model.live="perPage"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white dark:bg-neutral-800 dark:text-gray-200 dark:border-neutral-600 focus:outline-none focus:ring focus:border-blue-300">
                        <option value="10">10 rows</option>
                        <option value="25">25 rows</option>
                        <option value="50">50 rows</option>
                        <option value="100">100 rows</option>
                    </select>

                    <button wire:click="resetFilters"
                        class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-100 dark:bg-neutral-800 dark:text-gray-200 dark:border-neutral-600">
                        Reset
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto px-4 pb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">#</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Classification</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 cursor-pointer select-none"
                                wire:click="sortBy('score')">
                                Confidence Score
                                @if($sortField === 'score')
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                @else
                                    <span class="opacity-30">↕</span>
                                @endif
                            </th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Status</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300 cursor-pointer select-none"
                                wire:click="sortBy('created_at')">
                                Timestamp
                                @if($sortField === 'created_at')
                                    {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                @else
                                    <span class="opacity-30">↕</span>
                                @endif
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @forelse($classifications as $waste)
                            @php
                                $status       = $this->determineConfidenceStatus($waste->score);
                                $scorePercent = $waste->score * 100;
                            @endphp
                            <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer transition-all duration-200 hover:shadow-md"
                                wire:navigate
                                onclick="window.location='{{ route('classifications.show', $waste->id) }}'">
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 text-sm font-semibold {{ strtolower($waste->classification) === 'biodegradable' ? 'text-green-600 dark:text-green-400' : 'text-cyan-600 dark:text-cyan-400' }}">
                                    {{ ucfirst(strtolower($waste->classification)) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">{{ number_format($scorePercent, 2) }}%</td>
                                <td class="px-4 py-2">
                                    <span class="text-sm px-2 py-1 rounded
                                        @if($status === 'HIGH') bg-green-600 text-white
                                        @elseif($status === 'MEDIUM') bg-yellow-400
                                        @else bg-red-500 text-white
                                        @endif">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $waste->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 pb-4">
                {{ $classifications->links() }}
            </div>
        </div>

        {{-- Confidence Status Legend --}}
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-yellow-50 shadow dark:border-neutral-700 dark:bg-neutral-900">
            <div class="p-4 text-lg font-semibold text-gray-800 dark:text-white">
                Confidence Status Legend
            </div>
            <div class="overflow-x-auto px-4 pb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <tbody>
                        <tr>
                            <td class="py-2.5">
                                <span class="text-md text-white px-2 py-1 rounded bg-green-600">HIGH</span>
                                <span class="text-md pl-2">Score ≥ {{ $accuracyThreshold }}% (High confidence detection)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2.5">
                                <span class="text-md px-2 py-1 rounded bg-yellow-400">MEDIUM</span>
                                <span class="text-md pl-2">Score {{ $accuracyThreshold - 1 }}-{{ $accuracyThreshold - 20 }}% (Moderate confidence)</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2.5">
                                <span class="text-md text-white px-2 py-1 rounded bg-red-600">LOW</span>
                                <span class="text-md pl-2">Score &lt; {{ $accuracyThreshold - 20 }}% (Low confidence, requires review)</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
