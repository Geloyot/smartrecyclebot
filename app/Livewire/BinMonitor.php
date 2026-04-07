<?php

namespace App\Livewire;

use App\Events\NotificationCreated;
use App\Models\Bin;
use App\Models\BinReading;
use App\Models\Notification;
use App\Models\SystemThreshold;
use Livewire\Component;
use Livewire\WithPagination;

class BinMonitor extends Component
{
    use WithPagination;

    public $fullThreshold;

    // Per-table filters/sort
    public $bioSortField      = 'created_at';
    public $bioSortDir        = 'desc';
    public $bioFilterStatus   = '';
    public $bioPerPage        = 10;

    public $nonbioSortField     = 'created_at';
    public $nonbioSortDir       = 'desc';
    public $nonbioFilterStatus  = '';
    public $nonbioPerPage       = 10;

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

    public function sortBio(string $field): void
    {
        if ($this->bioSortField === $field) {
            $this->bioSortDir = $this->bioSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->bioSortField = $field;
            $this->bioSortDir   = 'desc';
        }
        $this->resetPage('bioPage');
    }

    public function sortNonBio(string $field): void
    {
        if ($this->nonbioSortField === $field) {
            $this->nonbioSortDir = $this->nonbioSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->nonbioSortField = $field;
            $this->nonbioSortDir   = 'desc';
        }
        $this->resetPage('nonbioPage');
    }

    public function updatedBioFilterStatus()  { $this->resetPage('bioPage'); }
    public function updatedBioPerPage()        { $this->resetPage('bioPage'); }
    public function updatedNonbioFilterStatus(){ $this->resetPage('nonbioPage'); }
    public function updatedNonbioPerPage()     { $this->resetPage('nonbioPage'); }

    public function determineStatus($fill): string
    {
        if ($fill === null) return 'Unknown';

        if ($fill >= $this->fullThreshold) {
            return 'FULL';
        } elseif ($fill >= $this->fullThreshold - 20) {
            return 'NEAR FULL';
        } elseif ($fill >= $this->fullThreshold - 40) {
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

            if ($status === 'FULL' && !$bin->notified_full) {
                $notif = Notification::create([
                    'user_id' => null,
                    'type'    => 'Bin Monitor',
                    'title'   => 'Bin is Full',
                    'message' => "Bin #{$bin->id} ({$bin->name}) is now full ({$fill}%).",
                    'level'   => 'warning',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $bin->update([
                    'notified_full'     => true,
                    'last_full_at'      => now(),
                    'last_full_fill_level' => $fill,
                ]);
            }

            if ($status !== 'FULL' && $bin->notified_full) {
                $bin->update(['notified_full' => false]);
            }

            $bin->last_fill_level = $fill;
            $bin->save();
        }
    }

    private function buildReadingsQuery(string $binType)
    {
        $bin = Bin::where('type', $binType)->first();
        if (!$bin) return null;

        $sortField     = $binType === 'bio' ? $this->bioSortField     : $this->nonbioSortField;
        $sortDir       = $binType === 'bio' ? $this->bioSortDir       : $this->nonbioSortDir;
        $filterStatus  = $binType === 'bio' ? $this->bioFilterStatus  : $this->nonbioFilterStatus;
        $perPage       = $binType === 'bio' ? $this->bioPerPage       : $this->nonbioPerPage;

        $query = BinReading::where('bin_id', $bin->id);

        if ($filterStatus) {
            $threshold = $this->fullThreshold;
            $low       = $threshold - 40;
            $half      = $threshold - 20;

            match ($filterStatus) {
                'FULL'      => $query->where('fill_level', '>=', $threshold),
                'NEAR FULL' => $query->whereBetween('fill_level', [$half, $threshold - 0.01]),
                'HALF'      => $query->whereBetween('fill_level', [$low, $half - 0.01]),
                'LOW'       => $query->where('fill_level', '<', $low),
            };
        }

        return $query->orderBy($sortField, $sortDir)->paginate($perPage, ['*'], "{$binType}Page");
    }

    private function getSummary(string $binType): array
    {
        $bin = Bin::where('type', $binType)->first();
        if (!$bin) return ['last_full_at' => null, 'last_emptied_at' => null, 'interval' => null];

        $lastFullAt    = $bin->last_full_at;
        $lastEmptiedAt = $bin->last_emptied_at;

        $interval = null;
        if ($lastFullAt && $lastEmptiedAt && $lastEmptiedAt->gt($lastFullAt)) {
            $interval = $lastEmptiedAt->diffForHumans($lastFullAt, true);
        }

        return [
            'last_full_at'    => $lastFullAt?->format('M d, Y H:i:s') ?? 'N/A',
            'last_emptied_at' => $lastEmptiedAt?->format('M d, Y H:i:s') ?? 'N/A',
            'interval'        => $interval ?? 'N/A',
        ];
    }

    public function render()
    {
        $bins = Bin::with(['readings' => fn($q) => $q->latest()->limit(1)])->get();

        $binsData = $bins->map(function ($bin) {
            $reading = $bin->readings->first();
            $fill    = $reading?->fill_level ?? null;
            $status  = $this->determineStatus($fill);

            if ($status === 'FULL' && !$bin->notified_full) {
                $notif = Notification::create([
                    'user_id' => null,
                    'type'    => 'Bin Monitor',
                    'title'   => 'Bin is Full',
                    'message' => "Bin #{$bin->id} ({$bin->name}) is now full ({$fill}%).",
                    'level'   => 'warning',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $bin->update([
                    'notified_full'        => true,
                    'last_full_at'         => now(),
                    'last_full_fill_level' => $fill,
                ]);
            }

            // Emptied detection (≥40% drop)
            if ($bin->notified_full
                && $bin->last_full_fill_level !== null
                && ($bin->last_full_fill_level - ($fill ?? 0)) >= 40
                && in_array($status, ['LOW', 'HALF'])
            ) {
                $drop  = $bin->last_full_fill_level - ($fill ?? 0);
                $notif = Notification::create([
                    'user_id' => null,
                    'type'    => 'Bin Monitor',
                    'title'   => 'Bin Emptied',
                    'message' => "Bin #{$bin->id} ({$bin->name}) was emptied (dropped by {$drop}%).",
                    'level'   => 'info',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $bin->update([
                    'notified_full'           => false,
                    'last_emptied_at'         => now(),
                    'last_emptied_full_at'    => $bin->last_full_at,
                    'last_full_fill_level'    => null,
                ]);
            }

            $bin->last_fill_level = $fill ?? 0;
            $bin->save();

            return [
                'id'         => $bin->id,
                'name'       => $bin->name,
                'type'       => $bin->type,
                'fill'       => $fill ?? 0,
                'status'     => $status,
                'updated_at' => $reading?->created_at?->format('M d, Y H:i:s') ?? 'N/A',
            ];
        });

        $fullBinCount    = $binsData->filter(fn($b) => $b['status'] === 'FULL')->count();
        $bioReadings     = $this->buildReadingsQuery('bio');
        $nonbioReadings  = $this->buildReadingsQuery('non-bio');
        $bioSummary      = $this->getSummary('bio');
        $nonbioSummary   = $this->getSummary('non-bio');

        return view('livewire.bin-monitor', compact(
            'binsData', 'fullBinCount',
            'bioReadings', 'nonbioReadings',
            'bioSummary', 'nonbioSummary'
        ));
    }
}
