@php
    use App\Models\User;
    use App\Models\WasteObject;
    use App\Models\BinReading;

    $totalUsers = User::count();
    $adminCount = User::where('role_id', 1)->count();
    $userCount = User::where('role_id', 2)->count();
    $lastUser = User::latest()->first();

    $fullThreshold = config('smartrecyclebot.full_bin_threshold', 80);
    $accuracyThreshold = config('smartrecyclebot.classification_accuracy_threshold', 80);

    $latestBioFill = BinReading::whereHas('bin', fn($q) => $q->where('type', 'bio'))
        ->latest('created_at')
        ->first()?->fill_level;
    $latestNonBioFill = BinReading::whereHas('bin', fn($q) => $q->where('type', 'non-bio'))
        ->latest('created_at')
        ->first()?->fill_level;
    $lastReadingTimestamp = BinReading::latest('created_at')->first()
        ? BinReading::latest('created_at')->first()->created_at->format('M d, Y H:i:s')
        : null;

    // Determine fill indicator dynamically
    function fillEmojiLabel($fill, $threshold) {
        if ($fill === null) return ['emoji' => '⚪', 'class' => 'text-gray-500'];
        if ($fill >= $threshold) return ['emoji' => '🔴', 'class' => 'text-red-700 dark:text-red-300'];
        if ($fill >= $threshold - 20) return ['emoji' => '🟠', 'class' => 'text-orange-700 dark:text-orange-300'];
        if ($fill >= $threshold - 40) return ['emoji' => '🟡', 'class' => 'text-yellow-700 dark:text-yellow-300'];
        return ['emoji' => '🟢', 'class' => 'text-green-700 dark:text-green-300'];
    }

    $bioIndicator = fillEmojiLabel($latestBioFill, $fullThreshold);
    $nonBioIndicator = fillEmojiLabel($latestNonBioFill, $fullThreshold);

    $score = $latestScore ?? 0;
    // thresholding for visual indicator
    $scorePercent = $score * 100;
    if ($scorePercent >= $accuracyThreshold) {
        $emoji = '🟢';
        $labelClass = 'text-green-700 dark:text-green-300';
    } elseif ($scorePercent >= $accuracyThreshold - 40) {
        $emoji = '🟡';
        $labelClass = 'text-yellow-700 dark:text-yellow-300';
    } else {
        $emoji = '🔴';
        $labelClass = 'text-red-700 dark:text-red-300';
    }

    // Summary values
    $totalClassifications = WasteObject::count();
    $lowConfidenceCount = WasteObject::whereNotNull('score')->where('score', '<', 0.60)->count();

    $recentClassifications = WasteObject::orderBy('created_at', 'desc')->limit(10)->get();  // Recent classifications (max 10)

    $lastClassificationTimestamp = optional($recentClassifications->first())->created_at
        ? optional($recentClassifications->first())->created_at->toDayDateTimeString()
        : null;
@endphp

<x-layouts.app :title="__('Dashboard (Admin)')">
    <h1 class="text-2xl font-bold mb-6">Administrator Dashboard</h1>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="grid auto-rows-min gap-4 md:grid-cols-2">
            {{-- CARD-SIZED SECTION TO DISPLAY USER STATISTICS --}}
            <div class="rounded-xl border border-green-200 dark:border-green-700 p-6 bg-green-50 dark:bg-green-900 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">User Summary</h2>

                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <p>👥 <strong class="font-medium">{{ $totalUsers }}</strong> total users</p>

                    <div class="pl-4 space-y-1">
                        <p>🔧 Admins: <strong>{{ $adminCount }}</strong></p>
                        <p>🙍 Regular Users: <strong>{{ $userCount }}</strong></p>
                    </div>
                </div>

                <div class="border-t border-neutral-300 dark:border-neutral-600 pt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest User</h3>
                    @if ($lastUser)
                        <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $lastUser->name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Joined {{ $lastUser->created_at->diffForHumans() }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No users yet.</p>
                    @endif
                </div>
            </div>
            {{-- CARD-SIZED SECTION TO DISPLAY BIN MONITORING STATISTICS --}}
            <div class="rounded-xl border border-green-200 dark:border-green-700 p-6 bg-green-50 dark:bg-green-900 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bin Monitoring</h2>
                <div class="text-sm text-gray-700 dark:text-gray-300 space-y-2">
                    <p>
                        {!! $bioIndicator['emoji'] !!}
                        <strong>{{ $latestBioFill ?? 0 }}%</strong> Biodegradable Fill
                    </p>
                    <p>
                        {!! $nonBioIndicator['emoji'] !!}
                        <strong>{{ $latestNonBioFill ?? 0 }}%</strong> Non-Biodegradable Fill
                    </p>
                </div>

                <div class="border-t border-neutral-300 dark:border-neutral-600 pt-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Update</h3>
                    @if($lastReadingTimestamp)
                        <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $lastReadingTimestamp }}
                        </p>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No readings yet.</p>
                    @endif
                </div>
            </div>
        </div>
        {{-- SECTION TO DISPLAY SEGREGATION RESULTS --}}
        <div class="rounded-xl border border-indigo-200 dark:border-indigo-700 p-6 bg-indigo-50 dark:bg-indigo-900 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Classification Results</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Recent object detection summaries (max 10).</p>
                </div>

                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $totalClassifications }}</p>

                    @if($lowConfidenceCount > 0)
                        <span class="inline-flex items-center px-2 py-0.5 mt-2 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">
                            ⚠ {{ $lowConfidenceCount }} low confidence
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 mt-2 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                            ✅ No recent low-confidence
                        </span>
                    @endif
                </div>
            </div>

            {{-- Latest single summary (keeps previous layout) --}}
            <div class="border-t border-neutral-300 dark:border-neutral-600 pt-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Latest Detection</h3>

                @if($recentClassifications->isNotEmpty())
                    @php
                        $latest = $recentClassifications->first();
                        $score = $latest->score ?? 0;
                        $scorePercent = $score * 100;
                        if ($scorePercent >= $accuracyThreshold) {
                            $emoji = '🟢';
                            $labelClass = 'text-green-700 dark:text-green-300';
                        } elseif ($scorePercent >= $accuracyThreshold - 40) {
                            $emoji = '🟡';
                            $labelClass = 'text-yellow-700 dark:text-yellow-300';
                        } else {
                            $emoji = '🔴';
                            $labelClass = 'text-red-700 dark:text-red-300';
                        }
                    @endphp

                    <div class="mt-2 flex items-center justify-between">
                        <div>
                            <p class="text-base font-semibold text-gray-900 dark:text-white">
                                <span class="{{ $labelClass }}">{{ $emoji }}</span>
                                <span class="ml-2">{{ $latest->classification }}</span>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">({{ $latest->score !== null ? number_format($latest->score * 100, 1) . '%' : '—' }})</span>
                            </p>

                            @if(!empty($latest->model_name) || !empty($latest->bin_id))
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    @if(!empty($latest->model_name)) Model: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $latest->model_name }}</span> @endif
                                    @if(!empty($latest->bin_id)) • Bin: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $latest->bin_id }}</span> @endif
                                </p>
                            @endif
                        </div>

                        <div class="ml-4">
                            <a href="{{ route('classification') ?? '#' }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium">
                                Review
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No classifications yet.</p>
                @endif
            </div>

            {{-- Recent list (max 10) --}}
            <div class="border-t border-neutral-300 dark:border-neutral-600 pt-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Recent Classifications</h3>

                @if($recentClassifications->isNotEmpty())
                    <ul class="mt-3 space-y-3">
                        @foreach($recentClassifications as $c)
                            @php
                                $s = $c->score ?? 0;
                                $at = $accuracyThreshold / 100;
                                if ($s >= $at) {
                                    $badge = 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
                                } elseif ($s >= $at - 0.40) {
                                    $badge = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300';
                                } else {
                                    $badge = 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300';
                                }
                            @endphp

                            <li class="flex items-center justify-between">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white/60 dark:bg-black/20 text-sm font-medium text-neutral-800 dark:text-neutral-100">
                                            {{ strtoupper(substr($c->classification ?? '—', 0, 1)) }}
                                        </span>
                                        <div class="truncate">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $c->classification ?? 'Unknown' }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $c->model_name ?? '—' }}
                                                @if(!empty($c->bin_id)) • Bin {{ $c->bin_id }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4 ml-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">
                                        {{ $c->score !== null ? number_format($c->score * 100, 0) . '%' : '—' }}
                                    </span>

                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ optional($c->created_at)->diffForHumans() }}
                                    </span>

                                    <a href="{{ route('classifications.show', $c->id) ?? route('classification') }}" class="text-xs text-indigo-600 dark:text-indigo-300 hover:underline ml-2">
                                        View
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">No recent classifications.</p>
                @endif
            </div>

            {{-- footer: last update --}}
            <div class="border-t border-neutral-300 dark:border-neutral-600 pt-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Classification</h3>
                @if(!empty($lastClassificationTimestamp))
                    <p class="mt-1 text-base font-semibold text-gray-900 dark:text-white">{{ $lastClassificationTimestamp }}</p>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">No recent classifications.</p>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
