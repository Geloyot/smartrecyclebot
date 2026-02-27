<?php

namespace App\Livewire;

use App\Models\WasteObject;
use Livewire\Component;
use App\Models\Notification;
use App\Models\SystemThreshold;
use App\Events\NotificationCreated;

class WasteClassify extends Component
{
    public $classifications = [];
    public $stats = [];

    public $accuracyThreshold;

    public function mount()
    {
        $this->accuracyThreshold = SystemThreshold::getValue('classification_accuracy_threshold', 80);
        $this->loadData();
    }

    public function saveThreshold()
    {
        SystemThreshold::setValue('classification_accuracy_threshold', $this->accuracyThreshold);
        session()->flash('threshold_saved', "Classification accuracy threshold updated to {$this->accuracyThreshold}% successfully!");
        $this->reevaluateClassifications();
    }

    public function loadData()
    {
        $today = now()->startOfDay();

        $this->classifications = WasteObject::latest()->take(10)->get();

        $this->stats = [
            'total_today' => WasteObject::where('created_at', '>=', $today)->count(),
            'biodegradable' => WasteObject::where('classification', 'Biodegradable')->count(),
            'non_biodegradable' => WasteObject::where('classification', 'Non-Biodegradable')->count(),
        ];
    }

    /**
     * Determine confidence status based on score
     */
    public function determineConfidenceStatus($score)
    {
        if ($score === null) {
            return 'Unknown';
        }

        $scorePercent = $score * 100;

        if ($scorePercent >= $this->accuracyThreshold) {
            return 'HIGH';
        } elseif ($scorePercent >= $this->accuracyThreshold - 20) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }

    /**
     * Re-evaluate existing classifications against new threshold
     */
    public function reevaluateClassifications()
    {
        $classifications = WasteObject::whereNotNull('score')->get();

        foreach ($classifications as $classification) {
            $score = $classification->score;
            $scorePercent = $score * 100;
            $status = $this->determineConfidenceStatus($score);

            // Create notification for LOW confidence if not already notified
            if ($status === 'LOW' && !$classification->notified_low_confidence) {
                $notif = Notification::create([
                    'user_id' => null,
                    'type' => 'Classification',
                    'title' => 'Low Confidence Detection',
                    'message' => "Classification #{$classification->id} ({$classification->classification}) has low confidence (" . number_format($scorePercent, 1) . "%).",
                    'level' => 'warning',
                    'is_read' => false,
                ]);
                event(new NotificationCreated($notif));

                $classification->update(['notified_low_confidence' => true]);
            }

            // Clear notification flag if confidence improved
            if ($status !== 'LOW' && $classification->notified_low_confidence) {
                $classification->update(['notified_low_confidence' => false]);
            }
        }
    }

    public function render()
    {
        return view('livewire.waste-classify');
    }
}
