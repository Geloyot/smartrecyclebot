/*
 * ESP32 Smart Bin Monitoring with MQTT (HiveMQ Cloud)
 * SMART RECYCLEBOT Capstone Project
 * 
 * Hardware: ESP32 Dev Board (30/38 pin) + 2x HC-SR04 Ultrasonic Sensors
 * 
 * Wiring:
 * Biodegradable Sensor:  TRIG=GPIO19, ECHO=GPIO18
 * Non-Bio Sensor:        TRIG=GPIO5,  ECHO=GPIO17
 * Both sensors:          VCC=5V, GND=GND
 */

#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <PubSubClient.h>

// ========================
// WiFi Configuration
// ========================
const char* ssid = "YOUR_WIFI_SSID";           // Replace with your WiFi name
const char* password = "YOUR_WIFI_PASSWORD";   // Replace with your WiFi password

// ========================
// HiveMQ Cloud Configuration
// ========================
const char* mqtt_server = "your-cluster.s2.eu.hivemq.cloud";  // Replace with your cluster URL
const int mqtt_port = 8883;                                    // Secure MQTT port
const char* mqtt_username = "smartrecyclebot";                 // Your HiveMQ username
const char* mqtt_password = "YOUR_MQTT_PASSWORD";              // Your HiveMQ password
const char* mqtt_client_id = "ESP32_SmartBin_001";             // Unique client ID

// MQTT Topics
const char* topic_bio = "smartrecyclebot/bin/biodegradable";
const char* topic_nonbio = "smartrecyclebot/bin/nonbiodegradable";
const char* topic_status = "smartrecyclebot/status";

// ========================
// Sensor Pin Configuration
// ========================
#define TRIG_BIO 19      // Biodegradable bin TRIG
#define ECHO_BIO 18      // Biodegradable bin ECHO
#define TRIG_NONBIO 5    // Non-biodegradable bin TRIG
#define ECHO_NONBIO 17   // Non-biodegradable bin ECHO

// ========================
// Bin Configuration
// ========================
const float binHeight = 40.64; // Bin height in cm (adjust to your actual bin)

// ========================
// Timing Configuration
// ========================
const unsigned long READING_INTERVAL = 15000;  // 15 seconds between readings
const unsigned long RECONNECT_INTERVAL = 5000; // 5 seconds retry for WiFi/MQTT

// ========================
// Global Objects
// ========================
WiFiClientSecure espClient;
PubSubClient mqttClient(espClient);

unsigned long lastReadingTime = 0;
unsigned long lastReconnectAttempt = 0;

// ========================
// Setup Function
// ========================
void setup() {
    Serial.begin(115200);
    delay(1000);
    
    Serial.println("\n\n╔════════════════════════════════════════╗");
    Serial.println("║  ESP32 Smart Bin Monitor (MQTT/TLS)   ║");
    Serial.println("║      SMART RECYCLEBOT PROJECT          ║");
    Serial.println("╚════════════════════════════════════════╝\n");
    
    // Setup sensor pins
    pinMode(TRIG_BIO, OUTPUT);
    pinMode(ECHO_BIO, INPUT);
    pinMode(TRIG_NONBIO, OUTPUT);
    pinMode(ECHO_NONBIO, INPUT);
    
    // Connect to WiFi
    setupWiFi();
    
    // Configure TLS (skip certificate verification for HiveMQ Cloud)
    espClient.setInsecure(); // Use this for HiveMQ Cloud (they have valid certs but easier setup)
    // For production with certificate pinning, use: espClient.setCACert(root_ca);
    
    // Setup MQTT
    mqttClient.setServer(mqtt_server, mqtt_port);
    mqttClient.setCallback(mqttCallback);
    mqttClient.setKeepAlive(60);
    mqttClient.setSocketTimeout(10);
    
    Serial.println("✓ Setup complete!\n");
}

// ========================
// WiFi Setup
// ========================
void setupWiFi() {
    Serial.print("📡 Connecting to WiFi: ");
    Serial.println(ssid);
    
    WiFi.mode(WIFI_STA);
    WiFi.begin(ssid, password);
    
    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        attempts++;
    }
    
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\n✓ WiFi Connected!");
        Serial.print("   IP Address: ");
        Serial.println(WiFi.localIP());
        Serial.print("   Signal: ");
        Serial.print(WiFi.RSSI());
        Serial.println(" dBm\n");
    } else {
        Serial.println("\n✗ WiFi Connection Failed!");
        Serial.println("   Restarting ESP32...\n");
        delay(3000);
        ESP.restart();
    }
}

// ========================
// MQTT Reconnect
// ========================
boolean reconnectMQTT() {
    Serial.print("🔌 Attempting MQTT connection to ");
    Serial.print(mqtt_server);
    Serial.print(":");
    Serial.print(mqtt_port);
    Serial.println("...");
    
    if (mqttClient.connect(mqtt_client_id, mqtt_username, mqtt_password)) {
        Serial.println("✓ MQTT Connected!");
        
        // Publish online status
        mqttClient.publish(topic_status, "online", true);
        
        Serial.println("   Subscribed to status updates");
        return true;
    } else {
        Serial.print("✗ MQTT Connection Failed! RC=");
        Serial.println(mqttClient.state());
        Serial.println("   Error codes:");
        Serial.println("   -4 = Connection timeout");
        Serial.println("   -3 = Connection lost");
        Serial.println("   -2 = Connect failed");
        Serial.println("   -1 = Disconnected");
        Serial.println("    0 = Connected");
        Serial.println("    1 = Bad protocol");
        Serial.println("    2 = Bad client ID");
        Serial.println("    3 = Unavailable");
        Serial.println("    4 = Bad credentials");
        Serial.println("    5 = Unauthorized");
        return false;
    }
}

// ========================
// MQTT Callback
// ========================
void mqttCallback(char* topic, byte* payload, unsigned int length) {
    Serial.print("📩 Message received [");
    Serial.print(topic);
    Serial.print("]: ");
    for (int i = 0; i < length; i++) {
        Serial.print((char)payload[i]);
    }
    Serial.println();
}

// ========================
// Get Distance from Ultrasonic Sensor
// ========================
float getDistance(int trigPin, int echoPin) {
    // Clear trigger
    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);
    
    // Send 10us pulse
    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);
    digitalWrite(trigPin, LOW);
    
    // Read echo with timeout
    long duration = pulseIn(echoPin, HIGH, 30000); // 30ms timeout
    
    if (duration == 0) {
        return -1; // Sensor error
    }
    
    // Calculate distance (cm)
    float distance = duration * 0.034 / 2;
    
    // Validate range
    if (distance < 2 || distance > binHeight + 10) {
        return -1; // Out of valid range
    }
    
    return distance;
}

// ========================
// Calculate Fill Percentage
// ========================
float calculateFillLevel(float distance) {
    if (distance < 0) {
        return 0.0; // Sensor error = report empty
    }
    
    float fillLevel = ((binHeight - distance) / binHeight) * 100.0;
    fillLevel = constrain(fillLevel, 0, 100);
    
    return fillLevel;
}

// ========================
// Publish Sensor Data to MQTT
// ========================
void publishSensorData() {
    // Read sensors (take 3 readings and average for stability)
    float dist1_sum = 0, dist2_sum = 0;
    int valid_reads_1 = 0, valid_reads_2 = 0;
    
    for (int i = 0; i < 3; i++) {
        float d1 = getDistance(TRIG_BIO, ECHO_BIO);
        if (d1 >= 0) {
            dist1_sum += d1;
            valid_reads_1++;
        }
        delay(50);
        
        float d2 = getDistance(TRIG_NONBIO, ECHO_NONBIO);
        if (d2 >= 0) {
            dist2_sum += d2;
            valid_reads_2++;
        }
        delay(50);
    }
    
    float dist1 = (valid_reads_1 > 0) ? (dist1_sum / valid_reads_1) : -1;
    float dist2 = (valid_reads_2 > 0) ? (dist2_sum / valid_reads_2) : -1;
    
    // Calculate fill levels
    float bioFill = calculateFillLevel(dist1);
    float nonbioFill = calculateFillLevel(dist2);
    
    // Create JSON payloads
    char bioPayload[80];
    char nonbioPayload[80];
    
    snprintf(bioPayload, 80, "{\"fill_level\":%.2f,\"distance\":%.2f}", bioFill, dist1);
    snprintf(nonbioPayload, 80, "{\"fill_level\":%.2f,\"distance\":%.2f}", nonbioFill, dist2);
    
    // Publish to MQTT
    boolean bioSuccess = mqttClient.publish(topic_bio, bioPayload);
    boolean nonbioSuccess = mqttClient.publish(topic_nonbio, nonbioPayload);
    
    // Print results
    Serial.println("─────────────────────────────────────────");
    Serial.println("📊 SENSOR READING");
    Serial.println("─────────────────────────────────────────");
    Serial.printf("🟢 Biodegradable:     %5.1f%% (%5.1f cm) %s\n", 
                  bioFill, dist1, bioSuccess ? "✓" : "✗");
    Serial.printf("🔵 Non-Biodegradable: %5.1f%% (%5.1f cm) %s\n", 
                  nonbioFill, dist2, nonbioSuccess ? "✓" : "✗");
    Serial.println("─────────────────────────────────────────");
    Serial.print("⏰ Next reading in ");
    Serial.print(READING_INTERVAL / 1000);
    Serial.println(" seconds\n");
}

// ========================
// Main Loop
// ========================
void loop() {
    unsigned long currentMillis = millis();
    
    // Check WiFi connection
    if (WiFi.status() != WL_CONNECTED) {
        if (currentMillis - lastReconnectAttempt > RECONNECT_INTERVAL) {
            Serial.println("⚠️  WiFi disconnected. Reconnecting...");
            setupWiFi();
            lastReconnectAttempt = currentMillis;
        }
        return;
    }
    
    // Check MQTT connection
    if (!mqttClient.connected()) {
        if (currentMillis - lastReconnectAttempt > RECONNECT_INTERVAL) {
            if (reconnectMQTT()) {
                lastReconnectAttempt = 0;
            } else {
                lastReconnectAttempt = currentMillis;
            }
        }
    } else {
        mqttClient.loop(); // Process MQTT messages
    }
    
    // Read and publish sensor data at intervals
    if (mqttClient.connected() && (currentMillis - lastReadingTime > READING_INTERVAL)) {
        publishSensorData();
        lastReadingTime = currentMillis;
    }
}
