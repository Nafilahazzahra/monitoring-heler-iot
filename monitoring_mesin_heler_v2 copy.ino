#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ==========================================
// KONFIGURASI WIFI DAN MQTT
// ==========================================
const char* WIFI_SSID = "Cipika cipiki";
const char* WIFI_PASSWORD = "siapayaa";

// Isi dengan IP laptop yang menjalankan broker MQTT lokal.
const char* MQTT_HOST = "10.219.134.213";
const uint16_t MQTT_PORT = 1883;
const char* MQTT_TOPIC = "heler/sensor";
const char* MQTT_CLIENT_ID = "esp32-heler-monitor";
const char* MQTT_USERNAME = "";
const char* MQTT_PASSWORD = "";

// Tinggi maksimal bak / tangki air dalam cm untuk konversi ke persen.
const float TANK_HEIGHT_CM = 100.0;
const unsigned long PUBLISH_INTERVAL_MS = 250;
const unsigned long FLOW_SAMPLE_MS = 250;
const unsigned long VIBRATION_WINDOW_MS = 250;
const unsigned long VIBRATION_DEBOUNCE_US = 3000;
const unsigned int VIBRATION_ABNORMAL_THRESHOLD = 8;
const unsigned long TEMPERATURE_SAMPLE_MS = 250;

// ==========================================
// DEFINISI PIN DOIT ESP32 V1
// ==========================================
#define DS18B20_PIN       13
#define WATER_FLOW_PIN    18
#define TRIG_PIN          5
#define ECHO_PIN          19
#define GETAR_PIN         23
#define BUZZER_PIN        27

#define LED_HIJAU_LEVEL   15
#define LED_MERAH_LEVEL   14
#define LED_HIJAU_FLOW    32
#define LED_MERAH_FLOW    33

OneWire oneWire(DS18B20_PIN);
DallasTemperature sensors(&oneWire);
WiFiClient espClient;
PubSubClient mqttClient(espClient);

volatile unsigned int pulseCount = 0;
volatile unsigned int vibrationEdgeCount = 0;
volatile unsigned long lastVibrationInterruptUs = 0;
unsigned long lastFlowCheckMs = 0;
unsigned long lastPublishMs = 0;
unsigned long lastVibrationCheckMs = 0;
unsigned long lastTemperatureRequestMs = 0;

float currentFlowRate = 0.0;
bool waterFlowError = false;
unsigned int lastVibrationCount = 0;
String currentVibrationStatus = "Tidak Bergetar";
float currentTemperature = 0.0;

struct SensorPayload {
  float temperature;
  float flowRate;
  float waterLevel;
  float distanceCm;
  int vibrationValue;
  unsigned int vibrationCount;
  String vibrationStatus;
  String status;
};

void IRAM_ATTR pulseCounter() {
  pulseCount++;
}

void IRAM_ATTR vibrationCounter() {
  unsigned long nowUs = micros();

  if (nowUs - lastVibrationInterruptUs < VIBRATION_DEBOUNCE_US) {
    return;
  }

  vibrationEdgeCount++;
  lastVibrationInterruptUs = nowUs;
}

void connectToWiFi() {
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }

  Serial.print("Menghubungkan WiFi");
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.println("WiFi terhubung");
  Serial.print("IP ESP32: ");
  Serial.println(WiFi.localIP());
}

void connectToMqtt() {
  while (!mqttClient.connected()) {
    Serial.print("Menghubungkan MQTT ke ");
    Serial.print(MQTT_HOST);
    Serial.print(":");
    Serial.print(MQTT_PORT);
    Serial.print(" (IP ESP ");
    Serial.print(WiFi.localIP());
    Serial.print(", gateway ");
    Serial.print(WiFi.gatewayIP());
    Serial.print(") ... ");

    bool connected = false;
    if (strlen(MQTT_USERNAME) > 0) {
      connected = mqttClient.connect(MQTT_CLIENT_ID, MQTT_USERNAME, MQTT_PASSWORD);
    } else {
      connected = mqttClient.connect(MQTT_CLIENT_ID);
    }

    if (connected) {
      Serial.println("berhasil");
    } else {
      Serial.print("gagal, rc=");
      Serial.print(mqttClient.state());
      Serial.println(" coba lagi 5 detik");
      delay(5000);
    }
  }
}

float readDistanceCm() {
  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);
  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);
  digitalWrite(TRIG_PIN, LOW);

  long duration = pulseIn(ECHO_PIN, HIGH, 30000);
  if (duration <= 0) {
    return TANK_HEIGHT_CM;
  }

  return duration * 0.034 / 2.0;
}

float calculateWaterLevelPercent(float distanceCm) {
  float level = ((TANK_HEIGHT_CM - distanceCm) / TANK_HEIGHT_CM) * 100.0;

  if (level < 0) {
    level = 0;
  }

  if (level > 100) {
    level = 100;
  }

  return level;
}

void updateFlowRate() {
  unsigned long now = millis();
  unsigned long elapsed = now - lastFlowCheckMs;

  if (elapsed < FLOW_SAMPLE_MS) {
    return;
  }

  noInterrupts();
  unsigned int pulses = pulseCount;
  pulseCount = 0;
  interrupts();

  if (pulses == 0) {
    currentFlowRate = 0.0;
    waterFlowError = true;
  } else {
    currentFlowRate = ((1000.0 / elapsed) * pulses) / 7.5;
    waterFlowError = false;
  }

  lastFlowCheckMs = now;
}

void updateTemperature() {
  unsigned long now = millis();

  if (now - lastTemperatureRequestMs < TEMPERATURE_SAMPLE_MS) {
    return;
  }

  currentTemperature = sensors.getTempCByIndex(0);
  sensors.requestTemperatures();
  lastTemperatureRequestMs = now;
}

String resolveStatus(const String& vibrationStatus) {
  if (vibrationStatus == "Terlalu Bergetar (Tidak Normal)") {
    return "Critical";
  }

  if (vibrationStatus == "Normal") {
    return "Warning";
  }

  return "Optimal";
}

void updateVibrationStatus() {
  unsigned long now = millis();

  if (now - lastVibrationCheckMs < VIBRATION_WINDOW_MS) {
    return;
  }

  noInterrupts();
  unsigned int edgeCount = vibrationEdgeCount;
  vibrationEdgeCount = 0;
  interrupts();

  lastVibrationCount = edgeCount;

  if (edgeCount >= VIBRATION_ABNORMAL_THRESHOLD) {
    currentVibrationStatus = "Terlalu Bergetar (Tidak Normal)";
  } else if (edgeCount > 0) {
    currentVibrationStatus = "Normal";
  } else {
    currentVibrationStatus = "Tidak Bergetar";
  }

  lastVibrationCheckMs = now;
}

SensorPayload readSensors() {
  SensorPayload payload;

  updateFlowRate();
  updateTemperature();
  updateVibrationStatus();

  payload.vibrationValue = digitalRead(GETAR_PIN);
  payload.vibrationCount = lastVibrationCount;
  payload.vibrationStatus = currentVibrationStatus;

  payload.distanceCm = readDistanceCm();
  payload.waterLevel = calculateWaterLevelPercent(payload.distanceCm);
  payload.flowRate = currentFlowRate;

  payload.temperature = currentTemperature;
  payload.status = resolveStatus(payload.vibrationStatus);

  return payload;
}

void applyIndicators(const SensorPayload& payload) {
  bool levelRed = payload.distanceCm > 0 && payload.distanceCm <= 10.0;
  bool levelGreen = !levelRed;
  bool flowRed = payload.flowRate <= 0.0;
  bool flowGreen = !flowRed;
  bool triggerBuzzer = payload.vibrationStatus == "Terlalu Bergetar (Tidak Normal)";

  digitalWrite(LED_MERAH_LEVEL, levelRed ? HIGH : LOW);
  digitalWrite(LED_HIJAU_LEVEL, levelGreen ? HIGH : LOW);
  digitalWrite(LED_MERAH_FLOW, flowRed ? HIGH : LOW);
  digitalWrite(LED_HIJAU_FLOW, flowGreen ? HIGH : LOW);
  digitalWrite(BUZZER_PIN, triggerBuzzer ? HIGH : LOW);
}

void printSensorData(const SensorPayload& payload) {
  Serial.println();
  Serial.println("=== REAL-TIME SENSOR DATA MQTT ===");
  Serial.print("Temperature      : ");
  Serial.print(payload.temperature);
  Serial.println(" C");

  Serial.print("Water Flow Rate  : ");
  Serial.print(payload.flowRate);
  Serial.println(" L/min");

  Serial.print("Water Distance   : ");
  Serial.print(payload.distanceCm);
  Serial.println(" cm");

  Serial.print("Water Level      : ");
  Serial.print(payload.waterLevel);
  Serial.println(" %");

  Serial.print("Vibration Value  : ");
  Serial.println(payload.vibrationValue);

  Serial.print("Vibration Count  : ");
  Serial.println(payload.vibrationCount);

  Serial.print("Vibration Status : ");
  Serial.println(payload.vibrationStatus);

  Serial.print("Machine Status   : ");
  Serial.println(payload.status);
}

void publishSensorData(const SensorPayload& payload) {
  StaticJsonDocument<256> doc;
  char buffer[256];

  doc["temperature"] = payload.temperature;
  doc["flow_rate"] = payload.flowRate;
  doc["water_level"] = payload.waterLevel;
  doc["distance_cm"] = payload.distanceCm;
  doc["vibration_value"] = payload.vibrationValue;
  doc["vibration_count"] = payload.vibrationCount;
  doc["vibration_status"] = payload.vibrationStatus;
  doc["status"] = payload.status;

  size_t length = serializeJson(doc, buffer);
  bool success = mqttClient.publish(MQTT_TOPIC, buffer, length);

  Serial.print("Publish MQTT     : ");
  Serial.println(success ? "berhasil" : "gagal");
  Serial.print("Payload JSON     : ");
  Serial.println(buffer);
}

void setup() {
  Serial.begin(115200);

  pinMode(LED_HIJAU_LEVEL, OUTPUT);
  pinMode(LED_MERAH_LEVEL, OUTPUT);
  pinMode(LED_HIJAU_FLOW, OUTPUT);
  pinMode(LED_MERAH_FLOW, OUTPUT);
  pinMode(BUZZER_PIN, OUTPUT);
  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
  pinMode(GETAR_PIN, INPUT);
  pinMode(WATER_FLOW_PIN, INPUT_PULLUP);

  attachInterrupt(digitalPinToInterrupt(WATER_FLOW_PIN), pulseCounter, FALLING);
  attachInterrupt(digitalPinToInterrupt(GETAR_PIN), vibrationCounter, CHANGE);
  sensors.begin();
  sensors.setResolution(9);
  sensors.setWaitForConversion(false);
  sensors.requestTemperatures();

  mqttClient.setServer(MQTT_HOST, MQTT_PORT);

  lastFlowCheckMs = millis();
  lastPublishMs = millis();
  lastVibrationCheckMs = millis();
  lastTemperatureRequestMs = millis();

  connectToWiFi();
  connectToMqtt();

  Serial.println("Sistem monitoring MQTT siap.");
}

void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }

  if (!mqttClient.connected()) {
    connectToMqtt();
  }

  mqttClient.loop();

  SensorPayload payload = readSensors();
  applyIndicators(payload);

  if (millis() - lastPublishMs >= PUBLISH_INTERVAL_MS) {
    printSensorData(payload);
    publishSensorData(payload);
    lastPublishMs = millis();
  }

  delay(10);
}
