<?php

namespace App\Livewire;

use App\Models\WasteObject;
use App\Models\Notification;
use App\Models\SystemThreshold;
use App\Events\NotificationCreated;
use Livewire\Component;
use Livewire\WithPagination;

class WasteClassify extends Component
{
    use WithPagination;

    // Card filters
    public $filterTotal  = 'overall';
    public $filterBio    = 'overall';
    public $filterNonBio = 'overall';

    // Table filters/sort
    public $filterScore    = '';
    public $filterLabel    = '';
    public $sortField      = 'created_at';
    public $sortDirection  = 'desc';
    public $perPage        = 10;

    // Threshold
    public $accuracyThreshold;

    public function mount()
    {
        $this->accuracyThreshold = SystemThreshold::getValue('classification_accuracy_threshold', 80);
    }

    // Reset pagination when filters change
    public function updatedFilterScore()  { $this->resetPage(); }
    public function updatedFilterLabel()  { $this->resetPage(); }
    public function updatedPerPage()      { $this->resetPage(); }

    public function saveThreshold()
    {
        SystemThreshold::setValue('classification_accuracy_threshold', $this->accuracyThreshold);
        session()->flash('threshold_saved', "Classification accuracy threshold updated to {$this->accuracyThreshold}% successfully!");
        $this->reevaluateClassifications();
    }

    public function setFilter(string $card, string $filter): void
    {
        match ($card) {
            'total'  => $this->filterTotal  = $filter,
            'bio'    => $this->filterBio    = $filter,
            'nonbio' => $this->filterNonBio = $filter,
        };
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterScore   = '';
        $this->filterLabel   = '';
        $this->sortField     = 'created_at';
        $this->sortDirection = 'desc';
        $this->perPage       = 10;
        $this->resetPage();
    }

    private function countByFilter(string $filter, ?string $classification = null): int
    {
        $query = WasteObject::query();

        if ($filter === 'today') {
            $query->where('created_at', '>=', now()->startOfDay());
        } elseif ($filter === 'week') {
            $query->where('created_at', '>=', now()->startOfWeek());
        }

        if ($classification) {
            $query->whereRaw('LOWER(classification) = ?', [strtolower($classification)]);
        }

        return $query->count();
    }

    public function determineConfidenceStatus($score)
    {
        if ($score === null) return 'Unknown';

        $scorePercent = $score * 100;

        if ($scorePercent >= $this->accuracyThreshold) {
            return 'HIGH';
        } elseif ($scorePercent >= $this->accuracyThreshold - 20) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    public function reevaluateClassifications()
    {
        $classifications = WasteObject::whereNotNull('score')->get();

        foreach ($classifications as $classification) {
            $score        = $classification->score;
            $scorePercent = $score * 100;
            $status       = $this->determineConfidenceStatus($score);

            if ($status === 'LOW' && !$classification->notified_low_confidence) {
                $notif = Notification::create([
                    'user_id' => null,
                    'type'    => 'Classification',
                    'title'   => 'Low Confidence Detection',
                    'message' => "Classification #{$classification->id} ({$classification->classification}) has low confidence (" . number_format($scorePercent, 1) . "%).",
                    'level'   => 'warning',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));
                $classification->update(['notified_low_confidence' => true]);
            }

            if ($status !== 'LOW' && $classification->notified_low_confidence) {
                $classification->update(['notified_low_confidence' => false]);
            }
        }
    }

    public function render()
    {
        $threshold = $this->accuracyThreshold;
        $low       = $threshold - 20;

        // Stats for cards
        $stats = [
            'total_count'       => $this->countByFilter($this->filterTotal),
            'biodegradable'     => $this->countByFilter($this->filterBio, 'Biodegradable'),
            'non_biodegradable' => $this->countByFilter($this->filterNonBio, 'Non-Biodegradable'),
        ];

        // Table query
        $query = WasteObject::query();

        if ($this->filterLabel) {
            $query->whereRaw('LOWER(classification) = ?', [strtolower($this->filterLabel)]);
        }

        if ($this->filterScore) {
            match ($this->filterScore) {
                'HIGH'   => $query->where('score', '>=', $threshold / 100),
                'MEDIUM' => $query->whereBetween('score', [$low / 100, ($threshold - 0.01) / 100]),
                'LOW'    => $query->where('score', '<', $low / 100),
            };
        }

        $classifications = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.waste-classify', compact('stats', 'classifications'));
    }
}
