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
            <div class="rounded-xl border border-neutral-200 bg-yellow-50 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Classifications Today</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ $stats['total_today'] ?? '0' }}
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-green-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Biodegradable</div>
                <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ $stats['biodegradable'] ?? '0' }}
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-blue-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Non-Biodegradable</div>
                <div class="mt-2 text-2xl font-semibold text-cyan-600 dark:text-cyan-400">
                    {{ $stats['non_biodegradable'] ?? '0' }}
                </div>
            </div>
        </div>

        {{-- Table section --}}
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-yellow-50 shadow dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex items-center justify-between mb-4">
                <div class="p-4 text-lg font-semibold text-gray-800 dark:text-white">
                    Recent Classifications
                </div>
                <div>
                </div>
            </div>
            <div class="overflow-x-auto px-4 pb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">#</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Classification</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Confidence Score</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Status</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @forelse($classifications as $waste)
                            @php
                                $status = $this->determineConfidenceStatus($waste->score);
                                $scorePercent = $waste->score * 100;
                            @endphp
                            <tr class="hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer transition-all duration-200 hover:shadow-md" onclick="window.location='{{ route('classifications.show', $waste->id) }}'">
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $loop->iteration }}</td>
                                <td class="px-4 py-2 text-sm font-semibold {{ $waste->classification === 'Biodegradable' ? 'text-green-600 dark:text-green-400' : 'text-cyan-600 dark:text-cyan-400' }}">
                                    {{ $waste->classification }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">{{ number_format($scorePercent, 2) }}%</td>
                                <td class="px-4 py-2">
                                    <span class="text-sm px-2 py-1 rounded
                                        @if ($status === 'HIGH')
                                            {{ 'bg-green-600 text-white' }}
                                        @elseif ($status === 'MEDIUM')
                                            {{ 'bg-yellow-400' }}
                                        @else
                                            {{ 'bg-red-500 text-white' }}
                                        @endif
                                    ">
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
