<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CreateArmActionProd extends Command
{
    protected $signature = 'arm:action-prod {status} {description}';
    protected $description = 'Send an arm action log to production only. Status: S = SUCCESS, W = WARNING';

    public function handle()
    {
        $status      = strtoupper($this->argument('status'));
        $description = $this->argument('description');

        if (!in_array($status, ['S', 'W', 'SUCCESS', 'WARNING'])) {
            $this->error('Status must be S (SUCCESS) or W (WARNING).');
            return 1;
        }

        $status = in_array($status, ['S', 'SUCCESS']) ? 'SUCCESS' : 'WARNING';

        $url = 'https://smartrecyclebot-b86k.onrender.com/api/arm-actions';

        $this->line("Sending to production ({$url})...");

        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-Api-Key' => config('services.devices.api_key')])
                ->post($url, [
                    'description'  => $description,
                    'status'       => $status,
                    'performed_at' => now()->toDateTimeString(),
                ]);

            if ($response->successful()) {
                $this->info("✅ [production] Arm action saved successfully.");
            } else {
                $this->warn("⚠️  [production] Status {$response->status()}: {$response->body()}");
            }
        } catch (\Exception $e) {
            $this->error("❌ [production] Failed to connect: {$e->getMessage()}");
        }

        return 0;
    }
}
