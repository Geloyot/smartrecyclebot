<?php

namespace App\Console\Commands;

use App\Models\ArmAction;
use App\Models\Notification;
use App\Events\NotificationCreated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateArmAction extends Command
{
    protected $signature = 'arm:action {status} {description}';
    protected $description = 'Create a robotic arm log. Status: S = SUCCESS, W = WARNING';

    public function handle()
    {
        $status      = strtoupper($this->argument('status'));
        $description = $this->argument('description');

        if (!in_array($status, ['S', 'W', 'SUCCESS', 'WARNING'])) {
            $this->error('Status must be S (SUCCESS) or W (WARNING).');
            return 1;
        }

        $status = in_array($status, ['S', 'SUCCESS']) ? 'SUCCESS' : 'WARNING';

        $log = ArmAction::create([
            'description'  => $description,
            'status'       => $status,
            'performed_at' => now(),
        ]);

        $this->info("✅ Robotic arm log created: [{$status}] {$description}");

        if ($status === 'WARNING') {
            $notif = Notification::create([
                'user_id' => null,
                'type'    => 'Robotic Arm',
                'title'   => 'Robotic Arm Pickup Blocked',
                'message' => $description,
                'level'   => 'warning',
                'is_read' => false,
            ]);

            try {
                event(new NotificationCreated($notif));
            } catch (\Throwable $e) {
                Log::warning('Broadcast failed: ' . $e->getMessage());
            }

            $this->info('🔔 Notification generated for WARNING log.');
        }

        return 0;
    }
}
