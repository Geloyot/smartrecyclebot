<?php

namespace App\Http\Controllers;

use App\Events\NotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\ArmAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ArmActionController extends Controller
{
    public function store(Request $request)
    {
        $apiKey = $request->header('X-Api-Key');
        if (!$apiKey || $apiKey !== config('services.devices.api_key')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'waste_object_id' => 'nullable|exists:waste_objects,id',
            'description'     => 'required|string',
            'status'          => 'required|in:SUCCESS,WARNING',
            'performed_at'    => 'nullable|date',
        ]);

        try {
            $log = ArmAction::create([
                'waste_object_id' => $data['waste_object_id'] ?? null,
                'description'     => $data['description'],
                'status'          => $data['status'],
                'performed_at'    => $data['performed_at'] ?? now(),
            ]);

            // Generate notification for WARNING logs
            if ($log->status === 'WARNING') {
                $notif = \App\Models\Notification::create([
                    'user_id' => null,
                    'type'    => 'Robotic Arm',
                    'title'   => 'Robotic Arm Pickup Blocked',
                    'message' => $log->description,
                    'level'   => 'warning',
                    'is_read' => false,
                ]);

                try {
                    event(new NotificationCreated($notif));
                } catch (\Throwable $e) {
                    Log::warning('Broadcast failed: ' . $e->getMessage());
                }
            }

            return response()->json(['success' => true, 'id' => $log->id], 201);
        } catch (\Throwable $e) {
            Log::error('ArmAction store error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
