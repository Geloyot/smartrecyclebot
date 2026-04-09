<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\Bin;
use App\Models\BinReading;
use App\Models\Notification;
use App\Models\SystemThreshold;
use Illuminate\Support\Facades\Log;

class BinReadingObserver
{
    public function created(BinReading $reading): void
    {
        $bin       = Bin::find($reading->bin_id);
        $fill      = $reading->fill_level;
        $threshold = SystemThreshold::getValue('full_bin_threshold', 80);

        if (!$bin || $fill === null) return;

        $isFull = $fill >= $threshold;

        // FULL detection
        if ($isFull && !$bin->notified_full) {
            $notif = Notification::create([
                'user_id' => null,
                'type'    => 'Bin Monitor',
                'title'   => 'Bin is Full',
                'message' => "Bin #{$bin->id} ({$bin->name}) is now full ({$fill}%).",
                'level'   => 'warning',
                'is_read' => false,
            ]);

            try {
                event(new NotificationCreated($notif));
            } catch (\Throwable $e) {
                Log::warning('Broadcast failed: ' . $e->getMessage());
            }

            $bin->update([
                'notified_full'        => true,
                'last_full_at'         => now(),
                'last_full_fill_level' => $fill,
            ]);
        }

        // EMPTIED detection (≥40% drop from last full level)
        if ($bin->notified_full
            && $bin->last_full_fill_level !== null
            && ($bin->last_full_fill_level - $fill) >= 40
            && in_array($this->determineStatus($fill, $threshold), ['LOW', 'HALF'])
        ) {
            $drop  = $bin->last_full_fill_level - $fill;
            $notif = Notification::create([
                'user_id' => null,
                'type'    => 'Bin Monitor',
                'title'   => 'Bin Emptied',
                'message' => "Bin #{$bin->id} ({$bin->name}) was emptied (dropped by {$drop}%).",
                'level'   => 'info',
                'is_read' => false,
            ]);

            try {
                event(new NotificationCreated($notif));
            } catch (\Throwable $e) {
                Log::warning('Broadcast failed: ' . $e->getMessage());
            }

            $bin->update([
                'notified_full'        => false,
                'last_emptied_at'      => now(),
                'last_emptied_full_at' => $bin->last_full_at,
                'last_full_fill_level' => null,
            ]);
        }

        if (!$isFull && !$bin->notified_full) {
            $bin->update(['notified_full' => false]);
        }

        $bin->update(['last_fill_level' => $fill]);
    }

    private function determineStatus(float $fill, float $threshold): string
    {
        if ($fill >= $threshold)      return 'FULL';
        if ($fill >= $threshold - 20) return 'NEAR FULL';
        if ($fill >= $threshold - 40) return 'HALF';
        return 'LOW';
    }
}
