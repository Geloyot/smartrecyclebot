<?php

namespace App\Services;

use App\Models\BinReading;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class MqttBinService
{
    protected $mqtt;
    protected $host;
    protected $port;
    protected $clientId;
    protected $username;
    protected $password;
    protected $useTls;

    public function __construct()
    {
        $this->host = env('MQTT_HOST', 'broker.hivemq.com');
        $this->port = (int) env('MQTT_PORT', 8883);
        $this->clientId = env('MQTT_CLIENT_ID', 'Laravel_SmartBin_' . uniqid());
        $this->username = env('MQTT_USERNAME', '');
        $this->password = env('MQTT_PASSWORD', '');
        $this->useTls = env('MQTT_USE_TLS', true);
    }

    /**
     * Start listening to MQTT topics
     */
    public function subscribe()
    {
        try {
            Log::info('MQTT: Attempting to connect to broker', [
                'host' => $this->host,
                'port' => $this->port,
                'client_id' => $this->clientId,
            ]);

            $connectionSettings = (new ConnectionSettings)
                ->setUsername($this->username)
                ->setPassword($this->password)
                ->setKeepAliveInterval(60)
                ->setLastWillTopic('smartrecyclebot/status')
                ->setLastWillMessage('offline')
                ->setLastWillQualityOfService(1)
                ->setConnectTimeout(10)
                ->setUseTls($this->useTls)
                ->setTlsSelfSignedAllowed(false); // HiveMQ Cloud uses valid certificates

            $this->mqtt = new MqttClient($this->host, $this->port, $this->clientId);
            $this->mqtt->connect($connectionSettings, true);

            Log::info('MQTT: Successfully connected to broker');

            // Subscribe to biodegradable bin topic
            $this->mqtt->subscribe('smartrecyclebot/bin/biodegradable', function ($topic, $message) {
                $this->handleBioMessage($message);
            }, 0);

            // Subscribe to non-biodegradable bin topic
            $this->mqtt->subscribe('smartrecyclebot/bin/nonbiodegradable', function ($topic, $message) {
                $this->handleNonBioMessage($message);
            }, 0);

            Log::info('MQTT: Subscribed to bin topics successfully');

            // Publish online status
            $this->mqtt->publish('smartrecyclebot/status', 'online', 0, true);

            // Keep connection alive and process messages
            $this->mqtt->loop(true);

        } catch (\Exception $e) {
            Log::error('MQTT Connection Error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle biodegradable bin message
     */
    protected function handleBioMessage($message)
    {
        try {
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('MQTT: Invalid JSON in bio message', ['message' => $message]);
                return;
            }

            if (isset($data['fill_level'])) {
                BinReading::create([
                    'bin_id' => 1, // Biodegradable bin
                    'fill_level' => $data['fill_level'],
                ]);

                Log::info("MQTT: Biodegradable bin reading saved", [
                    'fill_level' => $data['fill_level'],
                    'distance' => $data['distance'] ?? null
                ]);
            } else {
                Log::warning('MQTT: No fill_level in bio message', ['data' => $data]);
            }
        } catch (\Exception $e) {
            Log::error('MQTT: Error processing bio message: ' . $e->getMessage(), [
                'message' => $message
            ]);
        }
    }

    /**
     * Handle non-biodegradable bin message
     */
    protected function handleNonBioMessage($message)
    {
        try {
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('MQTT: Invalid JSON in non-bio message', ['message' => $message]);
                return;
            }

            if (isset($data['fill_level'])) {
                BinReading::create([
                    'bin_id' => 2, // Non-biodegradable bin
                    'fill_level' => $data['fill_level'],
                ]);

                Log::info("MQTT: Non-biodegradable bin reading saved", [
                    'fill_level' => $data['fill_level'],
                    'distance' => $data['distance'] ?? null
                ]);
            } else {
                Log::warning('MQTT: No fill_level in non-bio message', ['data' => $data]);
            }
        } catch (\Exception $e) {
            Log::error('MQTT: Error processing non-bio message: ' . $e->getMessage(), [
                'message' => $message
            ]);
        }
    }

    /**
     * Disconnect from MQTT broker
     */
    public function disconnect()
    {
        if ($this->mqtt) {
            try {
                $this->mqtt->publish('smartrecyclebot/status', 'offline', 0, true);
                $this->mqtt->disconnect();
                Log::info('MQTT: Disconnected from broker');
            } catch (\Exception $e) {
                Log::error('MQTT: Error during disconnect: ' . $e->getMessage());
            }
        }
    }
}
