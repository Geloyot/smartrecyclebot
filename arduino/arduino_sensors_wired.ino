
// Sensor 1 pins
#define TRIG1 9
#define ECHO1 10

// Sensor 2 pins
#define TRIG2 3
#define ECHO2 4

// Bin height in cm
float binHeight = 40.64;

void setup()
{
    Serial.begin(9600);

    // Set pin modes for Sensor 1
    pinMode(TRIG1, OUTPUT);
    pinMode(ECHO1, INPUT);

    // Set pin modes for Sensor 2
    pinMode(TRIG2, OUTPUT);
    pinMode(ECHO2, INPUT);

    delay(1000);
}

float getDistance(int trigPin, int echoPin)
{
    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);
    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);
    digitalWrite(trigPin, LOW);

    long duration = pulseIn(echoPin, HIGH, 30000); // 30ms timeout

    if (duration == 0)
        return 999;

    float distance = duration * 0.034 / 2;
    if (distance < 2 || distance > binHeight + 10)
        return 999; // out of range
    return distance;
}

void loop()
{
    float dist1 = getDistance(TRIG1, ECHO1);
    float dist2 = getDistance(TRIG2, ECHO2);

    Serial.println("-----------------------");

    float fill1, fill2;

    // Handle Bin 1
    if (dist1 == 999) {
        fill1 = 0.0;
    } else {
        fill1 = ((binHeight - dist1) / binHeight) * 100;
        fill1 = constrain(fill1, 0, 100);
    }

    // Handle Bin 2
    if (dist2 == 999) {
        fill2 = 0.0;
    } else {
        fill2 = ((binHeight - dist2) / binHeight) * 100;
        fill2 = constrain(fill2, 0, 100);
    }

    // Print a single line: “BIO:xx.xx,NONBIO:yy.yy”
    Serial.print("BIO:");
    Serial.print(fill1, 2);
    Serial.print(",NONBIO:");
    Serial.println(fill2, 2);

    delay(15000);
}
