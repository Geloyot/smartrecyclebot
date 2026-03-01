<x-layouts.app :title="__('Camera Control Panel')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <!-- Camera Controls Section -->
        <div wire:ignore class="rounded-xl border border-neutral-200 bg-yellow-50 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
            <div class="pb-4 text-lg font-semibold text-gray-800 dark:text-white">
                Camera Control Panel
            </div>

            <!-- Video Preview -->
            <video id="browser-camera-preview" class="w-full max-w-2xl border-2 border-gray-300 rounded-lg mb-4" playsinline></video>

            <!-- Canvas for frame capture (hidden) -->
            <canvas id="browser-canvas" class="hidden"></canvas>

            <!-- Detection Results -->
            <div id="browser-detection-result" class="hidden p-4 bg-blue-50 dark:bg-blue-900 rounded-lg border border-blue-200 dark:border-blue-700">
                <div class="text-lg font-bold" id="result-classification">-</div>
                <div class="text-sm text-gray-600 dark:text-gray-400" id="result-confidence">-</div>
                <div class="text-xs text-gray-500 dark:text-gray-500" id="result-time">-</div>
            </div>

            {{-- Status Indicator --}}
            <div class="font-semibold text-neutral-700 dark:text-neutral-300 text-lg mb-4">
                <span id="browser-camera-status">Ready to start</span>
            </div>

            <!-- Wake Service Button -->
            <div class="mb-4">
                <button id="btn-wake-service" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition cursor-pointer disabled:bg-gray-400 disabled:cursor-not-allowed">
                    Wake Up Detection Service
                </button>
                <span id="wake-status" class="ml-3 text-sm text-gray-600 dark:text-gray-400"></span>
            </div>

            <!-- Camera Controls -->
            <div class="flex gap-4 mb-6">
                <button id="btn-start-browser-camera" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition cursor-pointer disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                    Start Camera
                </button>

                <button id="btn-stop-browser-camera" class="px-6 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition cursor-pointer disabled:bg-gray-400 disabled:cursor-not-allowed" disabled>
                    Stop Camera
                </button>

                <button id="btn-clear-logs" class="px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition cursor-pointer">
                    Clear Logs
                </button>
            </div>

            <!-- Console Log Area -->
            <div class="rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-900 p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-mono text-gray-400">Camera Detection Console</div>
                    <div class="text-xs font-mono text-gray-500" id="log-count">0 entries</div>
                </div>
                <div id="console-logs" class="font-mono text-sm h-64 overflow-y-auto space-y-1 bg-black rounded p-2">
                    <div class="text-gray-500">[System] Console initialized</div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>

{{-- Configuration --}}
<script>
    window.appConfig = {
        apiKey: '{{ config('services.devices.api_key') }}',
        csrfToken: '{{ csrf_token() }}'
    };
</script>

{{-- Console Logger Utility --}}
<script>
    (function() {
        const consoleLogsDiv = document.getElementById('console-logs');
        const logCountDiv = document.getElementById('log-count');
        let logCount = 1; // Start at 1 because we have initial message

        window.consoleLog = function(type, message) {
            if (!consoleLogsDiv) return;

            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = 'flex gap-2';

            let color, prefix;
            switch(type) {
                case 'info':
                    color = 'text-cyan-400';
                    prefix = 'INFO';
                    break;
                case 'success':
                    color = 'text-green-400';
                    prefix = 'SUCCESS';
                    break;
                case 'warning':
                    color = 'text-yellow-400';
                    prefix = 'WARN';
                    break;
                case 'error':
                    color = 'text-red-400';
                    prefix = 'ERROR';
                    break;
                default:
                    color = 'text-gray-400';
                    prefix = 'LOG';
            }

            logEntry.innerHTML = `
                <span class="text-gray-500">[${timestamp}]</span>
                <span class="${color}">[${prefix}]</span>
                <span class="text-gray-300">${message}</span>
            `;

            consoleLogsDiv.appendChild(logEntry);
            consoleLogsDiv.scrollTop = consoleLogsDiv.scrollHeight;

            logCount++;
            if (logCountDiv) {
                logCountDiv.textContent = `${logCount} entries`;
            }
        };

        // Clear logs button
        const clearBtn = document.getElementById('btn-clear-logs');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (consoleLogsDiv) {
                    consoleLogsDiv.innerHTML = '<div class="text-gray-500">[System] Console cleared</div>';
                    logCount = 1;
                    if (logCountDiv) {
                        logCountDiv.textContent = `${logCount} entries`;
                    }
                }
            });
        }
    })();
</script>

{{-- Wake Service Script --}}
<script>
    (function() {
        window.isDetectionServiceReady = false;

        async function autoWakeService() {
            consoleLog('info', 'Auto-waking detection service...');

            const wakeBtn = document.getElementById('btn-wake-service');
            const wakeStatus = document.getElementById('wake-status');
            const startCameraBtn = document.getElementById('btn-start-browser-camera');

            if (!wakeBtn || !wakeStatus) {
                consoleLog('error', 'Wake elements not found, retrying in 500ms');
                setTimeout(autoWakeService, 500);
                return;
            }

            wakeBtn.disabled = true;
            wakeBtn.textContent = 'Waking up...';
            wakeStatus.textContent = 'Attempting to wake service, please wait...';
            wakeStatus.className = 'ml-3 text-sm text-gray-600 dark:text-gray-400';

            try {
                consoleLog('info', 'Sending health check to Python service...');
                const response = await fetch('https://smartrecyclebot-python.onrender.com/health', {
                    signal: AbortSignal.timeout(15000)
                });
                const data = await response.json();

                if (data.status === 'ok') {
                    consoleLog('success', 'Detection service is ready!');
                    window.isDetectionServiceReady = true;

                    if (startCameraBtn) {
                        startCameraBtn.disabled = false;
                    }

                    wakeBtn.textContent = 'Service Ready ✓';
                    wakeBtn.disabled = true;
                    wakeBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    wakeBtn.classList.add('bg-green-600');

                    wakeStatus.textContent = 'Detection service is ready!';
                    wakeStatus.className = 'ml-3 text-sm text-green-600 font-semibold';
                } else {
                    throw new Error('Service not ready');
                }
            } catch (error) {
                consoleLog('warning', `Service not responding: ${error.name}`);
                window.isDetectionServiceReady = false;

                wakeBtn.textContent = 'Wake Up Detection Service';
                wakeBtn.disabled = false;
                wakeBtn.classList.remove('bg-green-600');
                wakeBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');

                wakeStatus.textContent = 'Service is sleeping. Click button to wake it up.';
                wakeStatus.className = 'ml-3 text-sm text-yellow-600 dark:text-yellow-400';
            }
        }

        async function manualWakeService() {
            consoleLog('info', 'Manual wake button clicked');

            const btn = document.getElementById('btn-wake-service');
            const status = document.getElementById('wake-status');
            const startCameraBtn = document.getElementById('btn-start-browser-camera');

            if (!btn || !status) {
                consoleLog('error', 'Wake button elements not found!');
                alert('Error: Wake button elements not found. Please refresh the page.');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Waking up...';
            status.textContent = 'Please wait 30-60 seconds...';
            status.className = 'ml-3 text-sm text-gray-600 dark:text-gray-400';

            consoleLog('info', 'Waking Python service, this may take up to 60 seconds...');

            try {
                const response = await fetch('https://smartrecyclebot-python.onrender.com/health');
                const data = await response.json();

                if (data.status === 'ok') {
                    consoleLog('success', 'Detection service woken up successfully!');
                    window.isDetectionServiceReady = true;

                    if (startCameraBtn) {
                        startCameraBtn.disabled = false;
                    }

                    btn.textContent = 'Service Ready ✓';
                    btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    btn.classList.add('bg-green-600');
                    status.textContent = 'Detection service is ready! You can now start the camera.';
                    status.className = 'ml-3 text-sm text-green-600 font-semibold';
                } else {
                    throw new Error('Service not ready');
                }
            } catch (error) {
                consoleLog('error', `Failed to wake service: ${error.message}`);
                window.isDetectionServiceReady = false;
                btn.textContent = 'Wake Up Detection Service';
                btn.disabled = false;
                status.textContent = 'Failed to wake service. Try again.';
                status.className = 'ml-3 text-sm text-red-600';
            }
        }

        function initializeWakeService(attempt = 1) {
            const btn = document.getElementById('btn-wake-service');
            const status = document.getElementById('wake-status');
            const startCameraBtn = document.getElementById('btn-start-browser-camera');

            if (!btn || !status) {
                if (attempt < 10) {
                    setTimeout(() => initializeWakeService(attempt + 1), attempt * 100);
                } else {
                    consoleLog('error', 'Failed to find wake button elements after 10 attempts');
                }
                return;
            }

            if (startCameraBtn) {
                startCameraBtn.disabled = true;
            }

            btn.addEventListener('click', manualWakeService);
            autoWakeService();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initializeWakeService();
            });
        } else {
            initializeWakeService();
        }

        document.addEventListener('livewire:navigated', () => {
            initializeWakeService();
        });
    })();
</script>

{{-- Browser Camera Script --}}
<script>
    (function() {
        let browserVideoStream = null;
        let browserCaptureInterval = null;
        let isBrowserCapturing = false;
        let currentAbortController = null;

        const browserVideoElement = document.getElementById('browser-camera-preview');
        const browserCanvas = document.getElementById('browser-canvas');
        const browserContext = browserCanvas ? browserCanvas.getContext('2d') : null;
        const startBrowserBtn = document.getElementById('btn-start-browser-camera');
        const stopBrowserBtn = document.getElementById('btn-stop-browser-camera');
        const browserStatusDiv = document.getElementById('browser-camera-status');
        const resultDiv = document.getElementById('browser-detection-result');

        if (!browserVideoElement || !browserCanvas || !startBrowserBtn) {
            consoleLog('error', 'Camera elements not found');
            return;
        }

        browserVideoElement.setAttribute('playsinline', '');
        browserVideoElement.muted = true;
        browserVideoElement.autoplay = true;

        function updateBrowserStatus(message, isError = false) {
            if (browserStatusDiv) {
                browserStatusDiv.textContent = message;
                browserStatusDiv.className = `font-semibold text-lg mb-4 ${
                    isError
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-neutral-700 dark:text-neutral-300'
                }`;
            }
            consoleLog(isError ? 'error' : 'info', message);
        }

        function getErrorGuidance(err) {
            if (!err) return 'Unknown error occurred';

            const name = err.name || '';
            const message = err.message || '';

            if (name === 'NotReadableError' || message.toLowerCase().includes('could not start video source')) {
                return 'Camera is in use by another app. Close other apps/tabs using the camera and try again.';
            }
            if (name === 'NotAllowedError' || name === 'PermissionDenied') {
                return 'Camera permission denied. Please allow camera access in your browser settings.';
            }
            if (name === 'NotFoundError') {
                return 'No camera found on this device. Check your hardware.';
            }
            if (name === 'SecurityError') {
                return 'Camera access blocked for security reasons. Use HTTPS and check browser settings.';
            }
            if (name === 'AbortError') {
                return 'Camera request was aborted. Please try again.';
            }

            return `${name}: ${message}`;
        }

        async function checkCameraPermission() {
            try {
                if (navigator.permissions && navigator.permissions.query) {
                    const permission = await navigator.permissions.query({ name: 'camera' });
                    return permission.state;
                }
            } catch (err) {
                consoleLog('warning', 'Could not check camera permission');
            }
            return null;
        }

        function cleanupExistingStream() {
            if (browserVideoStream) {
                browserVideoStream.getTracks().forEach(track => {
                    track.stop();
                    consoleLog('info', `Stopped ${track.kind} track`);
                });
                browserVideoStream = null;
                browserVideoElement.srcObject = null;
            }
        }

        async function startBrowserCamera() {
            try {
                cleanupExistingStream();

                updateBrowserStatus('Checking camera availability...');

                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(d => d.kind === 'videoinput');

                if (videoDevices.length === 0) {
                    updateBrowserStatus('No camera detected on this device', true);
                    alert('No camera found. Check that your device has a camera and try again.');
                    return;
                }

                consoleLog('info', `Found ${videoDevices.length} camera(s)`);

                const permState = await checkCameraPermission();
                if (permState === 'denied') {
                    updateBrowserStatus('Camera permission denied', true);
                    alert('Camera permission is denied. Enable it in your browser settings and try again.');
                    return;
                }

                updateBrowserStatus('Requesting camera access...');

                const constraintAttempts = [
                    { video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'environment' } },
                    { video: { width: { ideal: 640 }, height: { ideal: 480 } } },
                    { video: true }
                ];

                let gotStream = null;
                let lastErr = null;

                for (const c of constraintAttempts) {
                    try {
                        gotStream = await navigator.mediaDevices.getUserMedia(c);
                        if (gotStream) {
                            consoleLog('success', 'Got camera stream successfully');
                            break;
                        }
                    } catch (err) {
                        lastErr = err;
                        consoleLog('warning', `getUserMedia attempt failed: ${err.name}`);
                    }
                }

                if (!gotStream) {
                    const guidance = getErrorGuidance(lastErr);
                    updateBrowserStatus(`Camera error: ${guidance}`, true);
                    alert(guidance);
                    return;
                }

                browserVideoStream = gotStream;
                browserVideoElement.srcObject = browserVideoStream;

                await new Promise((resolve, reject) => {
                    const onLoaded = () => {
                        browserVideoElement.removeEventListener('loadedmetadata', onLoaded);
                        resolve();
                    };
                    const onError = (e) => {
                        browserVideoElement.removeEventListener('error', onError);
                        reject(e);
                    };
                    browserVideoElement.addEventListener('loadedmetadata', onLoaded);
                    browserVideoElement.addEventListener('error', onError);
                    if (browserVideoElement.readyState >= 1) resolve();
                });

                try {
                    await browserVideoElement.play();
                } catch (playErr) {
                    consoleLog('warning', `video.play() failed: ${playErr.name}`);
                    if (playErr.name === 'NotAllowedError') {
                        updateBrowserStatus('Click the video to enable playback', true);
                        browserVideoElement.addEventListener('click', () => browserVideoElement.play(), { once: true });
                    }
                }

                startBrowserBtn.disabled = true;
                if (stopBrowserBtn) stopBrowserBtn.disabled = false;
                isBrowserCapturing = true;
                updateBrowserStatus('Camera started - Detecting objects every 10 seconds...');
                consoleLog('success', 'Camera started, detection interval: 10 seconds');

                if (!browserVideoElement.videoWidth || !browserVideoElement.videoHeight) {
                    await new Promise(r => setTimeout(r, 300));
                }

                startBrowserFrameCapture();

            } catch (error) {
                const guidance = getErrorGuidance(error);
                updateBrowserStatus(`Camera error: ${guidance}`, true);
                alert(guidance);
                cleanupExistingStream();
            }
        }

        function startBrowserFrameCapture() {
            if (!browserVideoElement.videoWidth || !browserVideoElement.videoHeight) {
                consoleLog('warning', 'Video dimensions not ready, delaying capture');
                setTimeout(startBrowserFrameCapture, 300);
                return;
            }

            consoleLog('info', `Starting frame capture (${browserVideoElement.videoWidth}x${browserVideoElement.videoHeight})`);

            browserCaptureInterval = setInterval(async () => {
                if (!isBrowserCapturing || !browserVideoStream) return;

                try {
                    browserCanvas.width = browserVideoElement.videoWidth;
                    browserCanvas.height = browserVideoElement.videoHeight;
                    browserContext.drawImage(browserVideoElement, 0, 0, browserCanvas.width, browserCanvas.height);

                    browserCanvas.toBlob(async (blob) => {
                        if (!blob || !isBrowserCapturing) return;

                        updateBrowserStatus('Sending to Python service...');
                        consoleLog('info', 'Captured frame, sending to Python service');

                        try {
                            const formData = new FormData();
                            formData.append('file', blob, 'frame.jpg');

                            currentAbortController = new AbortController();

                            let pythonResponse;
                            let retries = 3;

                            for (let i = 0; i < retries; i++) {
                                if (!isBrowserCapturing) {
                                    consoleLog('warning', 'Detection cancelled by user');
                                    updateBrowserStatus('Detection cancelled');
                                    return;
                                }

                                try {
                                    updateBrowserStatus(`Sending to Python service... (attempt ${i + 1}/${retries})`);

                                    pythonResponse = await fetch('https://smartrecyclebot-python.onrender.com/infer', {
                                        method: 'POST',
                                        body: formData,
                                        signal: currentAbortController ? currentAbortController.signal : undefined
                                    });

                                    if (pythonResponse.ok) break;

                                    if (pythonResponse.status === 503 && i < retries - 1) {
                                        consoleLog('warning', 'Service is waking up, waiting 5 seconds...');
                                        updateBrowserStatus('Service is waking up, please wait...');
                                        await new Promise(r => setTimeout(r, 5000));
                                        continue;
                                    }
                                } catch (err) {
                                    if (err.name === 'AbortError') {
                                        consoleLog('warning', 'Fetch aborted by user');
                                        updateBrowserStatus('Request cancelled');
                                        return;
                                    }

                                    if (i < retries - 1) {
                                        consoleLog('warning', `Connection failed, retrying in 3 seconds... (${i+1}/${retries})`);
                                        updateBrowserStatus('Connection failed, retrying...');
                                        await new Promise(r => setTimeout(r, 3000));
                                        continue;
                                    }
                                    throw err;
                                }
                            }

                            if (!isBrowserCapturing) {
                                consoleLog('warning', 'Detection cancelled after response');
                                return;
                            }

                            if (!pythonResponse.ok) {
                                const errorText = await pythonResponse.text();
                                consoleLog('error', `Python service error: ${pythonResponse.status}`);
                                updateBrowserStatus('Python service error: ' + pythonResponse.status, true);
                                return;
                            }

                            const detectionResult = await pythonResponse.json();
                            consoleLog('info', `Received detection result: ${detectionResult.detections?.length || 0} objects`);

                            const detections = detectionResult.detections || [];
                            if (detections.length === 0) {
                                consoleLog('info', 'No objects detected, waiting for next frame');
                                updateBrowserStatus('No objects detected, waiting for next frame...');
                                return;
                            }

                            const bestDetection = detections.reduce((max, d) =>
                                (d.conf || 0) > (max.conf || 0) ? d : max
                            );

                            const classification = bestDetection.class_name || 'Unknown';
                            const score = bestDetection.conf || 0;

                            consoleLog('success', `Detected: ${classification} (${(score * 100).toFixed(1)}% confidence)`);
                            updateBrowserStatus('Posting result to Laravel...');

                            if (!isBrowserCapturing) {
                                consoleLog('warning', 'Detection cancelled before Laravel');
                                return;
                            }

                            const laravelResponse = await fetch('/api/waste-objects/webhook', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': window.appConfig.csrfToken,
                                    'X-Api-Key': window.appConfig.apiKey,
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    classification: classification,
                                    score: score,
                                    model_name: 'yolov8',
                                    captured_at: new Date().toISOString()
                                }),
                                signal: currentAbortController ? currentAbortController.signal : undefined
                            });

                            if (!isBrowserCapturing) {
                                consoleLog('warning', 'Detection cancelled before Laravel response');
                                return;
                            }

                            let laravelResponseText = '';
                            try {
                                laravelResponseText = await laravelResponse.text();
                            } catch (err) {
                                if (err.name === 'AbortError') {
                                    consoleLog('warning', 'Laravel request aborted');
                                    return;
                                }
                                consoleLog('error', 'Failed to read Laravel response');
                                updateBrowserStatus('Failed to read Laravel response', true);
                                return;
                            }

                            if (!laravelResponse.ok) {
                                consoleLog('error', `Laravel error: ${laravelResponse.status}`);
                                updateBrowserStatus('Laravel error: ' + (laravelResponseText || laravelResponse.statusText), true);
                                return;
                            }

                            let laravelResult;
                            try {
                                laravelResult = JSON.parse(laravelResponseText);
                            } catch (parseErr) {
                                consoleLog('error', 'Invalid Laravel response (JSON parse error)');
                                updateBrowserStatus('Invalid Laravel response', true);
                                return;
                            }

                            if (laravelResult.success) {
                                consoleLog('success', 'Detection saved to database successfully');
                                displayBrowserResult({ classification: classification, score: score });
                                updateBrowserStatus('Detection saved - Waiting 10s for next frame...');

                                if (typeof Livewire !== 'undefined') {
                                    Livewire.dispatch('$refresh');
                                }
                            } else {
                                consoleLog('error', `Laravel failed: ${laravelResult.message || 'Unknown error'}`);
                                updateBrowserStatus('Laravel failed: ' + (laravelResult.message || 'Unknown error'), true);
                            }

                        } catch (error) {
                            if (error.name === 'AbortError') {
                                consoleLog('warning', 'Detection aborted by user');
                                updateBrowserStatus('Detection cancelled');
                                return;
                            }
                            consoleLog('error', `Detection pipeline error: ${error.message}`);
                            updateBrowserStatus('Detection error: ' + error.message, true);
                        } finally {
                            currentAbortController = null;
                        }
                    }, 'image/jpeg', 0.85);

                } catch (error) {
                    consoleLog('error', `Frame capture error: ${error.message}`);
                    updateBrowserStatus('Frame capture error: ' + error.message, true);
                }

            }, 10000);
        }

        function stopBrowserCamera() {
            consoleLog('info', 'Stopping camera...');

            isBrowserCapturing = false;

            if (currentAbortController) {
                currentAbortController.abort();
                currentAbortController = null;
                consoleLog('info', 'Aborted ongoing requests');
            }

            if (browserCaptureInterval) {
                clearInterval(browserCaptureInterval);
                browserCaptureInterval = null;
                consoleLog('info', 'Cleared capture interval');
            }

            cleanupExistingStream();

            startBrowserBtn.disabled = false;
            if (stopBrowserBtn) stopBrowserBtn.disabled = true;

            updateBrowserStatus('Camera stopped - Ready to start again');
            consoleLog('success', 'Camera fully stopped');
        }

        function displayBrowserResult(data) {
            const classificationEl = document.getElementById('result-classification');
            const confidenceEl = document.getElementById('result-confidence');
            const timeEl = document.getElementById('result-time');

            if (!classificationEl || !confidenceEl || !timeEl) return;

            classificationEl.textContent = data.classification;
            classificationEl.className = `text-lg font-bold ${
                data.classification === 'Biodegradable'
                    ? 'text-green-600 dark:text-green-400'
                    : 'text-cyan-600 dark:text-cyan-400'
            }`;

            confidenceEl.textContent = `Confidence: ${(data.score * 100).toFixed(2)}%`;
            timeEl.textContent = `Detected at ${new Date().toLocaleTimeString()}`;

            if (resultDiv) {
                resultDiv.classList.remove('hidden');
                resultDiv.classList.add('animate-pulse');
                setTimeout(() => resultDiv.classList.remove('animate-pulse'), 500);
            }
        }

        startBrowserBtn.addEventListener('click', startBrowserCamera);
        if (stopBrowserBtn) {
            stopBrowserBtn.addEventListener('click', stopBrowserCamera);
        }

        window.addEventListener('beforeunload', () => {
            if (isBrowserCapturing) {
                stopBrowserCamera();
            }
        });

        consoleLog('success', 'Browser camera initialized successfully');
    })();
</script>
