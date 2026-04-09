<?php

namespace App\Console\Commands;

use App\Models\Bin;
use App\Models\BinReading;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CreateBinReadingApi extends Command
{
    protected $signature = 'bin:reading-api {bio} {nonbio}';
    protected $description = 'Send a bin reading to both APIs. 1st arg = Bio fill level, 2nd arg = Non-Bio fill level OR B/N label.';

    public function handle()
    {
        $first  = $this->argument('bio');
        $second = strtoupper($this->argument('nonbio'));

        // Mode 1: both numeric (e.g. 90 45)
        if (is_numeric($first) && is_numeric($second)) {
            $payload = ['bio' => (float) $first, 'nonbio' => (float) $second];
        }
        // Mode 2: single value + label (e.g. 90 B or 90 N)
        elseif (is_numeric($first) && in_array($second, ['B', 'N'])) {
            $payload = $second === 'B'
                ? ['bio' => (float) $first, 'nonbio' => 0]
                : ['bio' => 0, 'nonbio' => (float) $first];
        } else {
            $this->error('Invalid arguments. Usage: bin:reading-api 90 45 OR bin:reading-api 90 B');
            return 1;
        }

        if ($payload['bio'] < 0 || $payload['bio'] > 100 || $payload['nonbio'] < 0 || $payload['nonbio'] > 100) {
            $this->error('Fill levels must be between 0 and 100.');
            return 1;
        }

        $urls = [
            'local'      => 'http://localhost:8000/api/bin-reading-read',
            'production' => 'https://smartrecyclebot-b86k.onrender.com/api/bin-reading-read',
        ];

        foreach ($urls as $env => $url) {
            $this->line("Sending to {$env} ({$url})...");
            try {
                $response = Http::timeout(10)->post($url, $payload);
                if ($response->successful()) {
                    $this->info("✅ [{$env}] Reading saved successfully.");
                } else {
                    $this->warn("⚠️  [{$env}] Status {$response->status()}: {$response->body()}");
                }
            } catch (\Exception $e) {
                $this->error("❌ [{$env}] Failed to connect: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
