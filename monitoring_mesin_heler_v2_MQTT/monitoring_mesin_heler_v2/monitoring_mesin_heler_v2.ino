#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <OneWire.h>
#include <DallasTemperature.h>

// ==========================================
// WIFI DAN MQTT
// ==========================================
const char* WIFI_SSID = "8";
const char* WIFI_PASSWORD = "88888888";

const char* MQTT_HOST = "10.112.228.213";
const uint16_t MQTT_PORT = 1883;

const char* MQTT_TOPIC = "heler/sensor";
const char* MQTT_CLIENT_ID = "esp32-heler-monitor";

const char* MQTT_USERNAME = "";
const char* MQTT_PASSWORD = "";

const uint16_t MQTT_BUFFER_SIZE = 768;

// ==========================================
// KONFIGURASI UMUM
// ==========================================
const float TANK_HEIGHT_CM = 100.0;
const float LOW_WATER_HEIGHT_CM = 10.0;
const float HIGH_TEMPERATURE_C = 90.0;

const unsigned long PUBLISH_INTERVAL_MS = 1000;
const unsigned long TEMPERATURE_SAMPLE_MS = 1000;

// ==========================================
// KONFIGURASI SENSOR WATER FLOW
// ==========================================
const unsigned long FLOW_SAMPLE_MS = 1000;

const float YF_S201_CALIBRATION_FACTOR = 7.5;
const float PIPE_DIAMETER_M = 0.0127; 
const float PIPE_RADIUS_M = PIPE_DIAMETER_M / 2.0;
const float PIPE_AREA_M2 =
  3.14159265 * PIPE_RADIUS_M * PIPE_RADIUS_M;

const float FLOW_STOPPED_THRESHOLD = 0.01;
const float FLOW_NORMAL_THRESHOLD = 1.00;

// ==========================================
// KONFIGURASI SENSOR GETAR (FIX)
// ==========================================
const unsigned long VIBRATION_WINDOW_MS = 1000;
const unsigned long VIBRATION_CALIBRATION_MS = 5000;
const unsigned long VIBRATION_DEBOUNCE_US = 2000;

const int VIBRATION_NORMAL_EDGE_MARGIN = 15;
const int VIBRATION_ABNORMAL_EDGE_MARGIN = 120;
const int VIBRATION_ABNORMAL_CONFIRM_WINDOWS = 5;

#define VIBRATION_DEBUG true

// ==========================================
// PIN ESP32
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

// ==========================================
// OBJEK
// ==========================================
OneWire oneWire(DS18B20_PIN);
DallasTemperature sensors(&oneWire);

WiFiClient espClient;
PubSubClient mqttClient(espClient);

// ==========================================
// STRUCT PAYLOAD
// ==========================================
struct SensorPayload {

  float temperature;

  float flowRate;
  float flowVelocity;
  String flowStatus;

  float waterLevel;
  float waterHeightCm;
  float distanceCm;

  int vibrationRawPin;
  int vibrationCount;
  int vibrationBaseline;

  String vibrationStatus;
  String status;
};

// ==========================================
// VARIABEL GLOBAL
// ==========================================
volatile unsigned int pulseCount = 0;

volatile unsigned int vibrationEdgeCountIsr = 0;
volatile unsigned long lastVibrationEdgeUs = 0;

unsigned long lastFlowCheckMs = 0;
unsigned long lastPublishMs = 0;
unsigned long lastTemperatureRequestMs = 0;
unsigned long lastVibrationWindowMs = 0;

float currentFlowRate = 0.0;
float currentFlowVelocity = 0.0;
float currentTemperature = 0.0;

int vibrationIdleState = LOW;
int vibrationBaseline = 0;
int vibrationBaselineEdges = 0;

int lastVibrationMetric = 0;
int abnormalWindowCount = 0;

String currentVibrationStatus = "Tidak Bergetar";
String currentFlowStatus = "Air Tidak Mengalir";

// ==========================================
// INTERRUPT WATER FLOW
// ==========================================
void IRAM_ATTR pulseCounter() {
  pulseCount++;
}

// ==========================================
// INTERRUPT GETAR
// ==========================================
void IRAM_ATTR vibrationCounter() {

  unsigned long nowUs = micros();

  if (nowUs - lastVibrationEdgeUs <
      VIBRATION_DEBOUNCE_US) {
    return;
  }

  vibrationEdgeCountIsr++;

  lastVibrationEdgeUs = nowUs;
}

// ==========================================
// INISIALISASI INDIKATOR
// ==========================================
void initIndicators() {

  pinMode(LED_HIJAU_LEVEL, OUTPUT);
  pinMode(LED_MERAH_LEVEL, OUTPUT);

  pinMode(LED_HIJAU_FLOW, OUTPUT);
  pinMode(LED_MERAH_FLOW, OUTPUT);

  pinMode(BUZZER_PIN, OUTPUT);
}

// ==========================================
// WIFI
// ==========================================
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

  Serial.print("WiFi Terhubung : ");
  Serial.println(WiFi.localIP());
}

// ==========================================
// MQTT
// ==========================================
void connectToMqtt() {

  while (!mqttClient.connected()) {

    Serial.print("Menghubungkan MQTT... ");

    bool connected = false;

    if (strlen(MQTT_USERNAME) > 0) {

      connected = mqttClient.connect(
        MQTT_CLIENT_ID,
        MQTT_USERNAME,
        MQTT_PASSWORD
      );

    } else {

      connected = mqttClient.connect(
        MQTT_CLIENT_ID
      );
    }

    if (connected) {

      Serial.println("BERHASIL");

    } else {

      Serial.print("GAGAL rc=");
      Serial.println(mqttClient.state());

      delay(5000);
    }
  }
}

// ==========================================
// ULTRASONIK
// ==========================================
void initWaterLevelSensor() {

  pinMode(TRIG_PIN, OUTPUT);
  pinMode(ECHO_PIN, INPUT);
}

float readDistanceCm() {

  digitalWrite(TRIG_PIN, LOW);
  delayMicroseconds(2);

  digitalWrite(TRIG_PIN, HIGH);
  delayMicroseconds(10);

  digitalWrite(TRIG_PIN, LOW);

  long duration =
    pulseIn(ECHO_PIN, HIGH, 30000);

  if (duration <= 0) {
    return TANK_HEIGHT_CM;
  }

  return duration * 0.034 / 2.0;
}

// ==========================================
// LEVEL AIR
// ==========================================
float calculateWaterLevelPercent(
  float distanceCm
) {

  float level =
    ((TANK_HEIGHT_CM - distanceCm)
     / TANK_HEIGHT_CM) * 100.0;

  return constrain(level, 0.0, 100.0);
}

float calculateWaterHeightCm(
  float distanceCm
) {

  return constrain(
    TANK_HEIGHT_CM - distanceCm,
    0.0,
    TANK_HEIGHT_CM
  );
}

// ==========================================
// SENSOR WATER FLOW
// ==========================================
void initWaterFlowSensor() {

  pinMode(WATER_FLOW_PIN,
          INPUT_PULLUP);

  attachInterrupt(
    digitalPinToInterrupt(
      WATER_FLOW_PIN
    ),
    pulseCounter,
    FALLING
  );
}

float calculateFlowVelocity(
  float flowRateLMin
) {

  float flowRateM3S =
    (flowRateLMin / 1000.0) / 60.0;

  return flowRateM3S / PIPE_AREA_M2;
}

String resolveFlowStatus(
  float flowVelocity
) {

  if (flowVelocity <=
      FLOW_STOPPED_THRESHOLD) {

    return "Air Tidak Mengalir";
  }

  if (flowVelocity <=
      FLOW_NORMAL_THRESHOLD) {

    return "Air Mengalir";
  }

  return "Air Mengalir Deras";
}

void updateWaterFlow() {

  unsigned long now = millis();

  if (now - lastFlowCheckMs <
      FLOW_SAMPLE_MS) {
    return;
  }

  noInterrupts();

  unsigned int pulses = pulseCount;
  pulseCount = 0;

  interrupts();

  currentFlowRate =
    (pulses == 0)
      ? 0.0
      : ((1000.0 /
         (now - lastFlowCheckMs))
         * pulses) / YF_S201_CALIBRATION_FACTOR;

  currentFlowVelocity =
    calculateFlowVelocity(
      currentFlowRate
    );

  currentFlowStatus =
    resolveFlowStatus(
      currentFlowVelocity
    );

  lastFlowCheckMs = now;
}

// ==========================================
// SENSOR TEMPERATURE
// ==========================================
void initTemperatureSensor() {

  sensors.begin();

  sensors.setResolution(9);

  sensors.setWaitForConversion(false);

  sensors.requestTemperatures();
}

void updateTemperature() {

  unsigned long now = millis();

  if (now - lastTemperatureRequestMs <
      TEMPERATURE_SAMPLE_MS) {
    return;
  }

  currentTemperature =
    sensors.getTempCByIndex(0);

  sensors.requestTemperatures();

  lastTemperatureRequestMs = now;
}

// ==========================================
// KALIBRASI GETAR
// ==========================================
void initVibrationSensor() {

  pinMode(GETAR_PIN, INPUT);

  attachInterrupt(
    digitalPinToInterrupt(
      GETAR_PIN
    ),
    vibrationCounter,
    CHANGE
  );
}

void calibrateVibration(
  unsigned long durationMs
) {

  Serial.println();
  Serial.println("==========================");
  Serial.println("KALIBRASI GETAR");
  Serial.println("JANGAN GERAKKAN");
  Serial.println("==========================");

  unsigned long startMs = millis();

  int highSamples = 0;
  int lowSamples = 0;

  noInterrupts();
  vibrationEdgeCountIsr = 0;
  interrupts();

  while (millis() - startMs < durationMs) {

    int pinState = digitalRead(GETAR_PIN);

    if (pinState == HIGH) {
      highSamples++;
    } else {
      lowSamples++;
    }

    delay(1);
  }

  noInterrupts();

  int edgeSamples =
    vibrationEdgeCountIsr;

  vibrationEdgeCountIsr = 0;

  interrupts();

  vibrationIdleState =
    (highSamples >= lowSamples)
      ? HIGH
      : LOW;

  vibrationBaselineEdges =
    edgeSamples;

  Serial.print("Baseline Edge : ");
  Serial.println(vibrationBaselineEdges);

  Serial.println("KALIBRASI SELESAI");
}

// ==========================================
// UPDATE GETARAN
// ==========================================
void updateVibration() {

  unsigned long now = millis();

  if (now - lastVibrationWindowMs <
      VIBRATION_WINDOW_MS) {
    return;
  }

  noInterrupts();

  int edgeCount =
    vibrationEdgeCountIsr;

  vibrationEdgeCountIsr = 0;

  interrupts();

  int effectiveEdges =
    edgeCount - vibrationBaselineEdges;

  if (effectiveEdges < 0) {
    effectiveEdges = 0;
  }

  // ==========================
  // LOGIKA GETAR
  // ==========================

  // GETARAN SANGAT TINGGI
  if (effectiveEdges >=
      VIBRATION_ABNORMAL_EDGE_MARGIN) {

    abnormalWindowCount++;

  } else {

    abnormalWindowCount = 0;
  }

  // STATUS ABNORMAL
  if (abnormalWindowCount >=
      VIBRATION_ABNORMAL_CONFIRM_WINDOWS) {

    currentVibrationStatus =
      "Terlalu Bergetar (Tidak Normal)";
  }

  // STATUS NORMAL
  else if (effectiveEdges >=
           VIBRATION_NORMAL_EDGE_MARGIN) {

    currentVibrationStatus =
      "Normal";
  }

  // TIDAK BERGETAR
  else {

    currentVibrationStatus =
      "Tidak Bergetar";
  }

  lastVibrationMetric =
    effectiveEdges;

  // ==========================
  // DEBUG
  // ==========================
  #if VIBRATION_DEBUG

  Serial.print("[GETAR] ");

  Serial.print("edge=");
  Serial.print(edgeCount);

  Serial.print(" | effective=");
  Serial.print(effectiveEdges);

  Serial.print(" | abnormal=");
  Serial.print(abnormalWindowCount);

  Serial.print(" | status=");
  Serial.println(currentVibrationStatus);

  #endif

  lastVibrationWindowMs = now;
}

// ==========================================
// STATUS MESIN
// ==========================================
String resolveStatus(
  const String& vibrationStatus
) {

  if (vibrationStatus ==
      "Terlalu Bergetar (Tidak Normal)") {

    return "Tidak Normal";
  }

  if (vibrationStatus ==
      "Normal") {

    return "Normal";
  }

  return "Mesin Mati";
}

// ==========================================
// BACA SENSOR
// ==========================================
SensorPayload readSensors() {

  SensorPayload payload;

  updateWaterFlow();
  updateTemperature();
  updateVibration();

  payload.vibrationRawPin =
    digitalRead(GETAR_PIN);

  payload.vibrationCount =
    lastVibrationMetric;

  payload.vibrationBaseline =
    vibrationBaseline;

  payload.vibrationStatus =
    currentVibrationStatus;

  payload.distanceCm =
    readDistanceCm();

  payload.waterHeightCm =
    calculateWaterHeightCm(
      payload.distanceCm
    );

  payload.waterLevel =
    calculateWaterLevelPercent(
      payload.distanceCm
    );

  payload.flowRate =
    currentFlowRate;

  payload.flowVelocity =
    currentFlowVelocity;

  payload.flowStatus =
    currentFlowStatus;

  payload.temperature =
    currentTemperature;

  payload.status =
    resolveStatus(
      payload.vibrationStatus
    );

  return payload;
}

// ==========================================
// LED DAN BUZZER
// ==========================================
void applyIndicators(
  const SensorPayload& payload
) {

  bool levelRed =
    payload.waterHeightCm <
    LOW_WATER_HEIGHT_CM;

  bool flowRed =
    payload.flowStatus ==
    "Air Tidak Mengalir";

  bool buzzerOn =
    payload.vibrationStatus ==
    "Terlalu Bergetar (Tidak Normal)" ||
    payload.temperature >
    HIGH_TEMPERATURE_C;

  digitalWrite(
    LED_MERAH_LEVEL,
    levelRed ? HIGH : LOW
  );

  digitalWrite(
    LED_HIJAU_LEVEL,
    !levelRed ? HIGH : LOW
  );

  digitalWrite(
    LED_MERAH_FLOW,
    flowRed ? HIGH : LOW
  );

  digitalWrite(
    LED_HIJAU_FLOW,
    !flowRed ? HIGH : LOW
  );

  digitalWrite(
    BUZZER_PIN,
    buzzerOn ? HIGH : LOW
  );
}

// ==========================================
// SERIAL MONITOR
// ==========================================
void printSensorData(
  const SensorPayload& payload
) {

  Serial.println();
  Serial.println("===== SENSOR DATA =====");

  Serial.printf(
    "Temperature : %.2f C\n",
    payload.temperature
  );

  Serial.printf(
    "Flow Rate   : %.2f L/min\n",
    payload.flowRate
  );

  Serial.printf(
    "Flow Vel.   : %.3f m/s\n",
    payload.flowVelocity
  );

  Serial.printf(
    "Flow Status : %s\n",
    payload.flowStatus.c_str()
  );

  Serial.printf(
    "Distance    : %.2f cm\n",
    payload.distanceCm
  );

  Serial.printf(
    "Water Height: %.2f cm\n",
    payload.waterHeightCm
  );

  Serial.printf(
    "Water Level : %.2f %%\n",
    payload.waterLevel
  );

  Serial.printf(
    "Vibration   : %d\n",
    payload.vibrationCount
  );

  Serial.printf(
    "Getar Status: %s\n",
    payload.vibrationStatus.c_str()
  );

  Serial.printf(
    "Status Mesin: %s\n",
    payload.status.c_str()
  );
}

// ==========================================
// MQTT PUBLISH
// ==========================================
void publishSensorData(
  const SensorPayload& payload
) {

  StaticJsonDocument<448> doc;

  char buffer[448];

  doc["temperature"] =
    payload.temperature;

  doc["flow_rate"] =
    payload.flowRate;

  doc["flow_velocity"] =
    payload.flowVelocity;

  doc["flow_status"] =
    payload.flowStatus;

  doc["water_level"] =
    payload.waterLevel;

  doc["water_height_cm"] =
    payload.waterHeightCm;

  doc["distance_cm"] =
    payload.distanceCm;

  doc["vibration_value"] =
    payload.vibrationRawPin;

  doc["vibration_count"] =
    payload.vibrationCount;

  doc["vibration_status"] =
    payload.vibrationStatus;

  doc["status"] =
    payload.status;

  size_t length =
    serializeJson(doc, buffer);

  bool success =
    mqttClient.publish(
      MQTT_TOPIC,
      buffer,
      length
    );

  Serial.print("MQTT : ");

  Serial.println(
    success
      ? "BERHASIL"
      : "GAGAL"
  );

  Serial.println(buffer);
}

// ==========================================
// SETUP
// ==========================================
void setup() {

  Serial.begin(115200);

  delay(500);

  initIndicators();
  initWaterLevelSensor();
  initWaterFlowSensor();
  initVibrationSensor();
  initTemperatureSensor();

  mqttClient.setServer(
    MQTT_HOST,
    MQTT_PORT
  );

  mqttClient.setBufferSize(
    MQTT_BUFFER_SIZE
  );

  connectToWiFi();

  connectToMqtt();

  calibrateVibration(
    VIBRATION_CALIBRATION_MS
  );

  lastFlowCheckMs = millis();
  lastPublishMs = millis();
  lastTemperatureRequestMs = millis();
  lastVibrationWindowMs = millis();

  Serial.println();
  Serial.println(
    "SISTEM MONITORING SIAP"
  );
}

// ==========================================
// LOOP
// ==========================================
void loop() {

  if (WiFi.status() !=
      WL_CONNECTED) {

    connectToWiFi();
  }

  if (!mqttClient.connected()) {

    connectToMqtt();
  }

  mqttClient.loop();

  SensorPayload payload =
    readSensors();

  applyIndicators(payload);

  if (millis() - lastPublishMs >=
      PUBLISH_INTERVAL_MS) {

    printSensorData(payload);

    publishSensorData(payload);

    lastPublishMs = millis();
  }

  delay(10);
}
