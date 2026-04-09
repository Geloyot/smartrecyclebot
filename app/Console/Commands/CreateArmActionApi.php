<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CreateArmActionApi extends Command
{
    protected $signature = 'arm:action-api {status} {description}';
    protected $description = 'Send an arm action log to both local and production APIs. Status: S = SUCCESS, W = WARNING';

    public function handle()
    {
        $status      = strtoupper($this->argument('status'));
        $description = $this->argument('description');

        if (!in_array($status, ['S', 'W', 'SUCCESS', 'WARNING'])) {
            $this->error('Status must be S (SUCCESS) or W (WARNING).');
            return 1;
        }

        $status = in_array($status, ['S', 'SUCCESS']) ? 'SUCCESS' : 'WARNING';

        $urls = [
            'local'      => 'http://localhost:8000/api/arm-actions',
            'production' => 'https://smartrecyclebot-b86k.onrender.com/api/arm-actions',
        ];

        foreach ($urls as $env => $url) {
            $this->line("Sending to {$env} ({$url})...");

            try {
                $response = Http::timeout(10)
                    ->withHeaders(['X-Api-Key' => config('services.devices.api_key')])
                    ->post($url, [
                        'description'  => $description,
                        'status'       => $status,
                        'performed_at' => now()->toDateTimeString(),
                    ]);

                if ($response->successful()) {
                    $this->info("✅ [{$env}] Arm action saved successfully.");
                } else {
                    $this->warn("⚠️  [{$env}] Status {$response->status()}: {$response->body()}");
                }
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($env === 'local') {
                    $this->warn("⚠️  [local] Server not running, skipping.");
                } else {
                    $this->error("❌ [{$env}] Failed to connect: {$e->getMessage()}");
                }
            } catch (\Exception $e) {
                $this->error("❌ [{$env}] Failed to connect: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
