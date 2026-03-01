<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WasteObject;
use App\Models\Notification;
use App\Models\SystemThreshold;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function receive(Request $request)
    {
        // simple API key check
        $apiKey = $request->header('X-Api-Key');
        if (!$apiKey || $apiKey !== config('services.devices.api_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'request_id' => 'nullable|string',
            'bin_id' => 'nullable|exists:bins,id',
            'classification' => 'required|string|max:100',
            'score' => 'nullable|numeric|min:0|max:1',
            'model_name' => 'nullable|string|max:100',
            'captured_at' => 'nullable|date',
        ]);

        try {
            // Create waste_object record
            $w = WasteObject::create([
                'bin_id' => $data['bin_id'] ?? null,
                'classification' => $data['classification'],
                'score' => $data['score'] ?? null,
                'model_name' => $data['model_name'] ?? null,
            ]);

            // Create notification if score is below low threshold
            if (!empty($w) && $w->score !== null) {
                $accuracyThreshold = SystemThreshold::getValue('classification_accuracy_threshold', 80);
                $lowThreshold = ($accuracyThreshold - 20) / 100; // Convert to decimal (e.g., 60% = 0.60)

                if ($w->score < $lowThreshold) {
                    $scorePercent = number_format($w->score * 100, 1);
                    Notification::create([
                        'user_id' => null,
                        'type' => 'Classification',
                        'title' => 'Low Confidence Detection',
                        'message' => "Classification #{$w->id} ({$w->classification}) has low confidence ({$scorePercent}%).",
                        'level' => 'warning',
                        'is_read' => false,
                    ]);
                }
            }

            return response()->json(['success' => true, 'id' => $w->id], 201);
        } catch (\Throwable $e) {
            Log::error('Webhook receive error: ' . $e->getMessage(), ['payload' => $request->all()]);
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
