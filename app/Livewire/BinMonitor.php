<?php

namespace App\Livewire;

use App\Events\NotificationCreated;
use App\Models\Bin;
use App\Models\BinReading;
use App\Models\Notification;
use App\Models\SystemThreshold;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class BinMonitor extends Component
{
    public $fullThreshold;

    public function mount()
    {
        $this->fullThreshold = SystemThreshold::getValue('full_bin_threshold', 80);
    }

    public function saveThreshold()
    {
        SystemThreshold::setValue('full_bin_threshold', $this->fullThreshold);
        session()->flash('threshold_saved', "Full bin threshold updated to {$this->fullThreshold}% successfully!");
        $this->reevaluateBins();
    }

    /**
     * Determine the status of a bin based on its fill level.
     *
     * @param int|null $fill
     * @return string
     */
    public function determineStatus($fill)
    {
        if ($fill === null) {
            return 'Unknown';
        } elseif ($fill >= $this->fullThreshold) {
            return 'FULL';
        } elseif ($fill >= $this->fullThreshold - 20 && $fill < $this->fullThreshold) {
            return 'NEAR FULL';
        } elseif ($fill >= $this->fullThreshold - 20 && $fill < $this->fullThreshold - 40) {
            return 'HALF';
        } else {
            return 'LOW';
        }
    }

    public function reevaluateBins()
    {
        $bins = Bin::with('readings')->get();

        foreach ($bins as $bin) {
            $latestReading = $bin->readings->sortByDesc('created_at')->first();
            $fill = $latestReading?->fill_level;

            if ($fill === null) continue;

            $status = $this->determineStatus($fill);

            // Check FULL notification
            if ($status === 'FULL' && ! $bin->notified_full) {
                // Generate notification
                $notif = Notification::create([
                    'user_id' => null,
                    'type' => 'Bin Monitor',
                    'title' => 'Bin is Full',
                    'message' => "Bin #{$bin->id} ({$bin->name}) is now full ({$fill}%).",
                    'level' => 'warning',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $bin->update([
                    'notified_full' => true,
                    'last_full_fill_level' => $fill,
                ]);
            }

            // Suppress FULL if no longer above threshold
            if ($status !== 'FULL' && $bin->notified_full) {
                $bin->update([
                    'notified_full' => false,
                    'last_full_fill_level' => null,
                ]);
            }

            $bin->last_fill_level = $fill;
            $bin->save();
        }
    }

    public function render()
    {
        $bins = Bin::with(['readings' => function ($q) {
            $q->latest()->limit(1);
        }])->get();

        $binsData = $bins->map(function ($bin) {
            $reading = $bin->readings->first();
            $fill = $reading?->fill_level ?? null; // always defined
            $status = $this->determineStatus($fill);

            // FULL detection
            if ($status === 'FULL' && ! $bin->notified_full) {
                $notif = Notification::create([
                    'user_id' => null,
                    'type' => 'Bin Monitor',
                    'title' => 'Bin is Full',
                    'message' => "Bin #{$bin->id} ({$bin->name}) is now full ({$fill}%).",
                    'level' => 'warning',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $bin->update([
                    'notified_full' => true,
                    'last_full_fill_level' => $fill,
                ]);
            }

            // Emptied detection (≥40% drop from full)
            if ($bin->notified_full
                && $bin->last_full_fill_level !== null
                && ($bin->last_full_fill_level - ($fill ?? 0)) >= 40
            ) {
                $binLevelDrop = $bin->last_full_fill_level - ($fill ?? 0);
                $notif = Notification::create([
                    'user_id' => null,
                    'type' => 'Bin Monitor',
                    'title' => 'Bin Emptied',
                    'message' => "Bin #{$bin->id} ({$bin->name}) was emptied (dropped by {$binLevelDrop}%).",
                    'level' => 'info',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $bin->update([
                    'notified_full' => false,
                    'last_full_fill_level' => null,
                ]);
            }

            $bin->last_fill_level = $fill ?? 0;
            $bin->save();

            return [
                'id' => $bin->id,
                'name' => $bin->name,
                'type' => $bin->type,
                'fill' => $fill ?? 0,
                'status' => $status,
                'updated_at' => $reading?->created_at?->format('M d, Y H:i:s') ?? 'N/A',
            ];
        });

        $fullBinCount = $binsData->filter(fn($bin) => $bin['status'] === 'FULL')->count();

        $recentReadings = BinReading::with('bin')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($reading) {
                $fill = $reading?->fill_level ?? 0;
                return [
                    'timestamp' => $reading->created_at->format('M d, Y H:i:s'),
                    'bin_type' => $reading->bin?->type ?? 'unknown',
                    'fill_level' => $fill,
                    'status' => $this->determineStatus($fill),
                ];
            });

        return view('livewire.bin-monitor', [
            'binsData' => $binsData,
            'fullBinCount' => $fullBinCount,
            'recentReadings' => $recentReadings,
            'nextCheckTime' => now()->addMinutes(10)->format('M d, Y H:i:s'),
        ]);
    }
}
