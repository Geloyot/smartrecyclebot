<?php
// scripts/serial_reader_webhook.php
// Reads from Arduino via serial port and sends data to Render production

require __DIR__ . '/../vendor/autoload.php';

use lepiaf\SerialPort\SerialPort;
use lepiaf\SerialPort\Parser\SeparatorParser;
use lepiaf\SerialPort\Configure\TTYConfigure;
use lepiaf\SerialPort\Configure\WindowsConfigure;
use lepiaf\SerialPort\Exception\DeviceNotFound;
use lepiaf\SerialPort\Exception\DeviceNotAvailable;
use lepiaf\SerialPort\Exception\DeviceNotOpened;

// ==========================================
// CONFIGURATION
// ==========================================

// Choose target: 'local' or 'production'
$target = 'production'; // Change to 'local' for testing

$urls = [
    'local' => 'http://localhost:8000/api/bin-reading-read',
    'production' => 'https://smartrecyclebot-b86k.onrender.com/api/bin-reading-read'
];

$apiUrl = $urls[$target];

echo "╔══════════════════════════════════════════╗\n";
echo "║   Serial Reader with Webhook             ║\n";
echo "║   Target: " . strtoupper($target) . str_repeat(' ', 29 - strlen($target)) . "║\n";
echo "║   URL: " . substr($apiUrl, 0, 32) . str_repeat(' ', 32 - min(32, strlen($apiUrl))) . "║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// ==========================================
// SERIAL PORT DETECTION
// ==========================================

if (PHP_OS_FAMILY === 'Windows') {
    $candidates = [
        '\\\\.\\COM3',
        '\\\\.\\COM4',
        '\\\\.\\COM5',
        'COM3',
        'COM4',
        'COM5',
    ];
    $configure = new WindowsConfigure(9600);
} else {
    $candidates = array_merge(
        glob('/dev/ttyACM*') ?: [],
        glob('/dev/ttyUSB*') ?: []
    );
    $configure = new TTYConfigure();
}

if (empty($candidates)) {
    echo "✗ No serial port candidates found.\n";
    echo "  Windows: Check Device Manager for COM port\n";
    echo "  Linux: Check 'ls /dev/tty*'\n";
    exit(1);
}

$parser = new SeparatorParser("\n");
$serialPort = new SerialPort($parser, $configure);
$openedPort = null;

foreach ($candidates as $port) {
    try {
        echo "Trying to open '{$port}'...\n";
        $serialPort->open($port);
        $openedPort = $port;
        echo "✓ Successfully opened Port '{$port}'\n\n";
        break;
    } catch (DeviceNotFound $e) {
        echo "  • Not found: {$port}\n";
    } catch (DeviceNotAvailable $e) {
        echo "  • Not available: {$port}\n";
    } catch (DeviceNotOpened $e) {
        echo "  • Not opened: {$port}\n";
    } catch (\Throwable $e) {
        echo "  • Other error: " . $e->getMessage() . "\n";
    }
}

if (!$openedPort) {
    echo "\n✗ Could not open any ports. Check:\n";
    echo "  1. Arduino is plugged in via USB\n";
    echo "  2. Correct COM port in Device Manager (Windows)\n";
    echo "  3. Driver installed for Arduino\n";
    exit(1);
}

echo "╔══════════════════════════════════════════╗\n";
echo "║   Listening on {$openedPort} @ 9600 baud" . str_repeat(' ', 14 - strlen($openedPort)) . "║\n";
echo "║   Sending data to: " . strtoupper($target) . str_repeat(' ', 20 - strlen($target)) . "║\n";
echo "║   Press Ctrl+C to stop                   ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// ==========================================
// MAIN LOOP
// ==========================================

$readingCount = 0;
$successCount = 0;
$errorCount = 0;

while (true) {
    $raw = trim($serialPort->read());

    if ($raw === '') {
        usleep(200_000); // 200ms
        continue;
    }

    // Skip separator lines
    if (strpos($raw, 'BIO:') !== 0) {
        continue;
    }

    // Parse data: BIO:xx.xx,NONBIO:yy.yy
    if (preg_match('/^BIO:([\d.]+),NONBIO:([\d.]+)$/', $raw, $m)) {
        $bio = (float)$m[1];
        $nonbio = (float)$m[2];
        $readingCount++;

        echo "─────────────────────────────────────────\n";
        echo "📊 Reading #{$readingCount}\n";
        echo "─────────────────────────────────────────\n";
        echo "🟢 Biodegradable:     {$bio}%\n";
        echo "🔵 Non-Biodegradable: {$nonbio}%\n";
        echo "📡 Sending to {$target}...\n";

        // Send to API
        $query = http_build_query(compact('bio', 'nonbio'));
        $fullUrl = "{$apiUrl}?{$query}";

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($fullUrl, false, $context);

        if ($response === false) {
            $errorCount++;
            echo "✗ API Error: Connection failed\n";
            echo "  Check:\n";
            echo "  - Internet connection\n";
            echo "  - Render service is running\n";
            echo "  - URL is correct: {$apiUrl}\n";
        } else {
            $successCount++;
            echo "✓ API Response: {$response}\n";
        }

        echo "📈 Stats: {$successCount} success, {$errorCount} errors\n";
        echo "─────────────────────────────────────────\n\n";
    }
}
