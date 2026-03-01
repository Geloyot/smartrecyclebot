<?php

namespace App\Console\Commands;

use App\Services\MqttBinService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MqttBinSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mqtt:subscribe-bins';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to MQTT topics for bin monitoring (runs continuously)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   MQTT Bin Subscriber Starting...       ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();
        
        $this->info('📡 Connecting to MQTT broker: ' . env('MQTT_HOST'));
        $this->info('🔌 Port: ' . env('MQTT_PORT'));
        $this->info('👤 Client ID: ' . env('MQTT_CLIENT_ID'));
        $this->newLine();
        
        $this->warn('⚠️  Press Ctrl+C to stop (will run continuously)');
        $this->newLine();
        
        Log::info('MQTT Subscriber: Command started');
        
        $mqttService = new MqttBinService();
        
        // Handle graceful shutdown (UNIX only - skip on Windows)
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () use ($mqttService) {
                $this->warn('Received SIGTERM, disconnecting...');
                $mqttService->disconnect();
                exit(0);
            });
            pcntl_signal(SIGINT, function () use ($mqttService) {
                $this->warn('Received SIGINT, disconnecting...');
                $mqttService->disconnect();
                exit(0);
            });
        }
        
        try {
            $this->info('✓ Starting MQTT subscription...');
            $mqttService->subscribe();
        } catch (\Exception $e) {
            $this->error('✗ MQTT Subscription failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            $this->error('Check your configuration:');
            $this->error('  - MQTT_HOST is set correctly');
            $this->error('  - MQTT_USERNAME and MQTT_PASSWORD are correct');
            $this->error('  - HiveMQ cluster is running');
            $this->error('  - Network/firewall allows port ' . env('MQTT_PORT'));
            
            Log::error('MQTT Subscriber: Fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
        
        return 0;
    }
}