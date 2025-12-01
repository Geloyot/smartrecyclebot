<div wire:poll.30s>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Card section row --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="rounded-xl border border-neutral-200 bg-yellow-50 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Classifications Today</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ $stats['total_today'] ?? '0' }}
                </div>
            </div>

            <div
                class="rounded-xl border border-neutral-200 bg-green-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Biodegradable</div>
                <div class="mt-2 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ $stats['biodegradable'] ?? '0' }}
                </div>
            </div>

            <div
                class="rounded-xl border border-neutral-200 bg-blue-100 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Non-Biodegradable</div>
                <div class="mt-2 text-2xl font-semibold text-cyan-600 dark:text-cyan-400">
                    {{ $stats['non_biodegradable'] ?? '0' }}
                </div>
            </div>
        </div>

        {{-- ADD THIS: Browser Camera Section for All Users --}}
        @if (Auth::check() && Auth::user()->role_id == 2)
            <!-- Camera Controls Section -->
            <div
                class="rounded-xl border border-neutral-200 bg-yellow-50 p-4 shadow dark:border-neutral-700 dark:bg-neutral-900">
                <div class="pb-4 text-lg font-semibold text-gray-800 dark:text-white">
                    Detection Control Panel
                </div>
        
                <!-- Video Preview -->
                <video id="browser-camera-preview" class="w-full max-w-2xl border-2 border-gray-300 rounded-lg mb-4"
                    playsinline>
                </video>
        
                <!-- Canvas for frame capture (hidden) -->
                <canvas id="browser-canvas" class="hidden"></canvas>
        
                <!-- Detection Results -->
                <div id="browser-detection-result"
                    class="hidden p-4 bg-blue-50 dark:bg-blue-900 rounded-lg border border-blue-200 dark:border-blue-700">
                    <div class="text-lg font-bold" id="result-classification">-</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400" id="result-confidence">-</div>
                    <div class="text-xs text-gray-500 dark:text-gray-500" id="result-time">-</div>
                </div>
                
                {{-- Status Indicator --}}
                <div class="font-semibold text-neutral-700 dark:text-neutral-300 text-lg mb-4">
                    <span id="browser-camera-status">Ready to start</span>
                </div>
                
                <!-- Camera Controls -->
                <!-- Add before the Start Camera button -->
                <div class="mb-4">
                    <button id="btn-wake-service"
                        class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition cursor-pointer">
                        Wake Up Detection Service
                    </button>
                    <span id="wake-status" class="ml-3 text-sm text-gray-600 dark:text-gray-400"></span>
                </div>
                
                <script>
                    // Add this at the top of your camera script
                    (async function autoWakeService() {
                        console.log('[AutoWake] Waking up detection service...');
                        
                        try {
                            const response = await fetch('https://smartrecyclebot-python.onrender.com/health');
                            const data = await response.json();
                            
                            if (data.status === 'ok') {
                                console.log('[AutoWake] ✓ Detection service ready!');
                            }
                        } catch (error) {
                            console.warn('[AutoWake] Service still waking up:', error);
                        }
                    })();
                    document.getElementById('btn-wake-service').addEventListener('click', async function() {
                        const btn = this;
                        const status = document.getElementById('wake-status');
                        
                        btn.disabled = true;
                        btn.textContent = 'Waking up...';
                        status.textContent = 'Please wait 30-60 seconds...';
                        
                        try {
                            const response = await fetch('https://smartrecyclebot-python.onrender.com/health');
                            const data = await response.json();
                            
                            if (data.status === 'ok') {
                                btn.textContent = 'Service Ready ✓';
                                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                                btn.classList.add('bg-green-600');
                                status.textContent = 'Detection service is ready! You can now start the camera.';
                                status.classList.add('text-green-600', 'font-semibold');
                            }
                        } catch (error) {
                            btn.textContent = 'Wake Up Detection Service';
                            btn.disabled = false;
                            status.textContent = 'Failed to wake service. Try again.';
                            status.classList.add('text-red-600');
                        }
                    });
                </script>
                <div class="flex gap-4 mb-6">
                    <button id="btn-start-browser-camera"
                        class="px-6 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition cursor-pointer disabled:bg-gray-400 disabled:cursor-not-allowed">
                        Start Camera
                    </button>
        
                    <button id="btn-stop-browser-camera"
                        class="px-6 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition cursor-pointer disabled:bg-gray-400 disabled:cursor-not-allowed"
                        disabled>
                        Stop Camera
                    </button>
                </div>
            </div>
        @endif
        
        {{-- ADD THIS: Browser Camera JavaScript --}}
        <script>
            (function() {
                let browserVideoStream = null;
                let browserCaptureInterval = null;
                let isBrowserCapturing = false;
                let currentAbortController = null; // Add abort controller for fetch cancellation
        
                const browserVideoElement = document.getElementById('browser-camera-preview');
                const browserCanvas = document.getElementById('browser-canvas');
                const browserContext = browserCanvas ? browserCanvas.getContext('2d') : null;
                const startBrowserBtn = document.getElementById('btn-start-browser-camera');
                const stopBrowserBtn = document.getElementById('btn-stop-browser-camera');
                const browserStatusDiv = document.getElementById('browser-camera-status');
                const resultDiv = document.getElementById('browser-detection-result');
        
                if (!browserVideoElement || !browserCanvas || !startBrowserBtn) {
                    console.log('Browser camera elements not found');
                    return;
                }
        
                browserVideoElement.setAttribute('playsinline', '');
                browserVideoElement.muted = true;
                browserVideoElement.autoplay = true;
        
                function updateBrowserStatus(message, isError = false) {
                    if (browserStatusDiv) {
                        browserStatusDiv.textContent = message;
                        browserStatusDiv.className =
                            `text-sm ${isError ? 'text-red-600 font-semibold' : 'text-gray-600 dark:text-gray-400'}`;
                    }
                    console.log('[BrowserCamera] ' + message);
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
                            const permission = await navigator.permissions.query({
                                name: 'camera'
                            });
                            return permission.state;
                        }
                    } catch (err) {
                        console.warn('Could not check camera permission:', err);
                    }
                    return null;
                }
        
                function cleanupExistingStream() {
                    if (browserVideoStream) {
                        browserVideoStream.getTracks().forEach(track => {
                            track.stop();
                            console.log('Stopped track:', track.kind);
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
        
                        const permState = await checkCameraPermission();
                        if (permState === 'denied') {
                            updateBrowserStatus('Camera permission denied', true);
                            alert('Camera permission is denied. Enable it in your browser settings and try again.');
                            return;
                        }
        
                        updateBrowserStatus('Requesting camera access...');
        
                        const constraintAttempts = [{
                                video: {
                                    width: { ideal: 640 },
                                    height: { ideal: 480 },
                                    facingMode: 'environment'
                                }
                            },
                            {
                                video: {
                                    width: { ideal: 640 },
                                    height: { ideal: 480 }
                                }
                            },
                            { video: true }
                        ];
        
                        let gotStream = null;
                        let lastErr = null;
        
                        for (const c of constraintAttempts) {
                            try {
                                gotStream = await navigator.mediaDevices.getUserMedia(c);
                                if (gotStream) {
                                    console.log('Successfully got stream with constraints:', c);
                                    break;
                                }
                            } catch (err) {
                                lastErr = err;
                                console.warn('getUserMedia attempt failed for constraints', c, err);
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
                            console.warn('video.play() failed:', playErr);
                            if (playErr.name === 'NotAllowedError') {
                                updateBrowserStatus('Click the video to enable playback', true);
                                browserVideoElement.addEventListener('click', () => browserVideoElement.play(), {
                                    once: true
                                });
                            }
                        }
        
                        startBrowserBtn.disabled = true;
                        if (stopBrowserBtn) stopBrowserBtn.disabled = false;
                        isBrowserCapturing = true;
                        updateBrowserStatus('Camera started - Detecting objects every 3 seconds...');
        
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
                        console.warn('Video dimensions not ready yet, delaying capture...');
                        setTimeout(startBrowserFrameCapture, 300);
                        return;
                    }
        
                    let isProcessing = false;
        
                    browserCaptureInterval = setInterval(async () => {
                        if (!isBrowserCapturing || !browserVideoStream) return;
        
                        isProcessing = true;  // Block other requests
        
                        try {
                            browserCanvas.width = browserVideoElement.videoWidth;
                            browserCanvas.height = browserVideoElement.videoHeight;
                            browserContext.drawImage(browserVideoElement, 0, 0, browserCanvas.width,
                                browserCanvas.height);
        
                            browserCanvas.toBlob(async (blob) => {
                                if (!blob) return;
        
                                updateBrowserStatus('Sending to Python service...');
        
                                try {
                                    // 1. Send to Python /infer for detection
                                    const formData = new FormData();
                                    formData.append('file', blob, 'frame.jpg');
                                    
                                    // Create new AbortController for this request
                                    currentAbortController = new AbortController();
        
                                    // Retry logic for sleeping Render service
                                    let pythonResponse;
                                    let retries = 3;
                                    
                                    for (let i = 0; i < retries; i++) {
                                        // Check if user pressed stop
                                        if (!isBrowserCapturing) {
                                            console.log('Detection cancelled by user');
                                            updateBrowserStatus('Detection cancelled');
                                            return;
                                        }
        
                                        try {
                                            updateBrowserStatus(`Sending to Python service... (attempt ${i + 1}/${retries})`);
                                            
                                            pythonResponse = await fetch(
                                                'https://smartrecyclebot-python.onrender.com/infer', {
                                                    method: 'POST',
                                                    body: formData,
                                                    signal: currentAbortController ? currentAbortController.signal : undefined
                                                });
                                            
                                            if (pythonResponse.ok) break;
                                            
                                            // If 503, service is waking up - wait and retry
                                            if (pythonResponse.status === 503 && i < retries - 1) {
                                                updateBrowserStatus('Service is waking up, please wait...');
                                                await new Promise(r => setTimeout(r, 5000));
                                                continue;
                                            }
                                        } catch (err) {
                                            // Check if it was aborted by user
                                            if (err.name === 'AbortError') {
                                                console.log('Fetch aborted by user');
                                                updateBrowserStatus('Request cancelled');
                                                return;
                                            }
                                            
                                            if (i < retries - 1) {
                                                updateBrowserStatus('Connection failed, retrying...');
                                                await new Promise(r => setTimeout(r, 3000));
                                                continue;
                                            }
                                            throw err;
                                        }
                                    }
        
                                    // Check again if user stopped during request
                                    if (!isBrowserCapturing) {
                                        console.log('Detection cancelled after response');
                                        return;
                                    }
        
                                    if (!pythonResponse.ok) {
                                        const errorText = await pythonResponse.text();
                                        console.error('Python service error:', pythonResponse.status, errorText);
                                        updateBrowserStatus('Python service error: ' + pythonResponse.status, true);
                                        return;
                                    }
        
                                    const detectionResult = await pythonResponse.json();
                                    console.log('Detection result:', detectionResult);
        
                                    // 2. Extract classification and score from detections
                                    const detections = detectionResult.detections || [];
                                    if (detections.length === 0) {
                                        updateBrowserStatus('No objects detected', false);
                                        return;
                                    }
        
                                    // Get the best detection (highest confidence)
                                    const bestDetection = detections.reduce((max, d) =>
                                        (d.conf || 0) > (max.conf || 0) ? d : max
                                    );
        
                                    const classification = bestDetection.class_name || 'Unknown';
                                    const score = bestDetection.conf || 0;
        
                                    console.log('Best detection:', classification, score);
                                    updateBrowserStatus('Posting result to Laravel...');
        
                                    // Check one more time before Laravel request
                                    if (!isBrowserCapturing) {
                                        console.log('Detection cancelled before Laravel');
                                        return;
                                    }
        
                                    // 3. Send to Laravel webhook
                                    const laravelResponse = await fetch('/api/waste-objects/webhook', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'X-Api-Key': '{{ config('services.devices.api_key') }}',
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
        
                                    let laravelResponseText = '';
                                    try {
                                        laravelResponseText = await laravelResponse.text();
                                    } catch (err) {
                                        if (err.name === 'AbortError') {
                                            console.log('Laravel request aborted');
                                            return;
                                        }
                                        console.error('Failed to read Laravel response:', err);
                                        updateBrowserStatus('Failed to read Laravel response', true);
                                        return;
                                    }
        
                                    if (!laravelResponse.ok) {
                                        updateBrowserStatus('Laravel error: ' + (laravelResponseText || laravelResponse.statusText), true);
                                        console.error('Laravel error', laravelResponse.status, laravelResponseText);
                                        return;
                                    }
        
                                    let laravelResult;
                                    try {
                                        laravelResult = JSON.parse(laravelResponseText);
                                    } catch (parseErr) {
                                        console.error('Laravel JSON parse error:', laravelResponseText);
                                        updateBrowserStatus('Invalid Laravel response', true);
                                        return;
                                    }
        
                                    // 4. Display result
                                    if (laravelResult.success) {
                                        displayBrowserResult({
                                            classification: classification,
                                            score: score
                                        });
                                        updateBrowserStatus('Detection saved - Waiting 3s for next frame...');
                                        window.Livewire?.emit('$refresh');
                                    } else {
                                        updateBrowserStatus('Laravel failed: ' + (laravelResult.message || 'Unknown error'), true);
                                        console.error('Laravel result:', laravelResult);
                                    }
        
                                } catch (error) {
                                    if (error.name === 'AbortError') {
                                        console.log('Detection aborted by user');
                                        updateBrowserStatus('Detection cancelled');
                                        return;
                                    }
                                    console.error('Detection pipeline error:', error);
                                    updateBrowserStatus('Detection error: ' + error.message, true);
                                } finally {
                                    currentAbortController = null;
                                }
                            }, 'image/jpeg', 0.85);
        
                        } catch (error) {
                            console.error('Frame capture error:', error);
                            updateBrowserStatus('Frame capture error: ' + error.message, true);
                        } finally {
                            isProcessing = false;
                        }
        
                    }, 10000);
                }
        
                function stopBrowserCamera() {
                    isBrowserCapturing = false;
        
                    // Abort any ongoing fetch requests
                    if (currentAbortController) {
                        currentAbortController.abort();
                        currentAbortController = null;
                        console.log('Aborted ongoing requests');
                    }
        
                    if (browserCaptureInterval) {
                        clearInterval(browserCaptureInterval);
                        browserCaptureInterval = null;
                    }
        
                    cleanupExistingStream();
                    startBrowserBtn.disabled = false;
                    if (stopBrowserBtn) stopBrowserBtn.disabled = true;
                    updateBrowserStatus('Camera stopped');
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
        
                // Event listeners
                startBrowserBtn.addEventListener('click', startBrowserCamera);
                if (stopBrowserBtn) {
                    stopBrowserBtn.addEventListener('click', stopBrowserCamera);
                }
        
                // Cleanup on page unload
                window.addEventListener('beforeunload', () => {
                    if (isBrowserCapturing) stopBrowserCamera();
                });
            })();
        </script>

        {{-- Table section --}}
        <div
            class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-yellow-50 shadow dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex items-center justify-between mb-4">
                <div class="p-4 text-lg font-semibold text-gray-800 dark:text-white">
                    Recent Classifications
                </div>
                <div>
                    <a href="{{ route('classifications_export.pdf') }}"
                        class="mx-2 mt-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Export Classifications to PDF
                    </a>
                    <a href="{{ route('classifications_export.csv') }}"
                        class="mx-2 px-3 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                        Export Classifications to CSV
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto px-4 pb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                    <thead class="bg-gray-50 dark:bg-neutral-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">#</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">
                                Classification</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">
                                Confidence Score</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500 dark:text-gray-300">
                                Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                        @forelse($classifications as $waste)
                            <tr>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">{{ $loop->iteration }}
                                </td>
                                <td
                                    class="px-4 py-2 text-sm font-semibold {{ $waste->classification === 'Biodegradable' ? 'text-green-600 dark:text-green-400' : 'text-cyan-600 dark:text-cyan-400' }}">
                                    {{ $waste->classification }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                                    {{ number_format($waste->score * 100, 2) }}%</td>
                                <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $waste->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4"
                                    class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No data
                                    available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
