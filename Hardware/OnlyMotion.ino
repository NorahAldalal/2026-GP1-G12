#include <Arduino.h>
#include <TinyGPS++.h>
#include <HardwareSerial.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ── WiFi ─────────────────────────────────────────────────────

const char* SSID     = "wifi name";
const char* PASSWORD = "wifi password";
const char* SERVER   = "http://IP/Siraj/api/update_lamp.php";

// ── Lamp IDs ─────────────────────────────────────────────────
#define LAMP_PAIR1  1
#define LAMP_PAIR2  2

// ── Pins ─────────────────────────────────────────────────────
#define PIR1_PIN   34
#define PIR2_PIN   35
#define LDR1_PIN   32
#define LDR2_PIN   33
#define LED1A_PIN  18
#define LED1B_PIN  19
#define LED2A_PIN  26
#define LED2B_PIN  14
#define POT_PIN    36
#define GPS_RX     16
#define GPS_TX     17

// ── Brightness ───────────────────────────────────────────────
#define POT_THRESHOLD   1200
#define BRIGHT_HIGH      150   // عند وجود حركة
#define BRIGHT_LOW        40   // بدون حركة
#define MOTION_TIMEOUT  10000   // 10 s

// ── Upload interval ──────────────────────────────────────────
#define UPLOAD_INTERVAL 10000

// ── GPS ──────────────────────────────────────────────────────
TinyGPSPlus    gps;
HardwareSerial gpsSerial(2);

// ── State ────────────────────────────────────────────────────
unsigned long lastMotion1 = 0;
unsigned long lastMotion2 = 0;
unsigned long lastUpload  = 0;


// ── Send data to database ─────────────────────────────────────
void sendToDB(int lampID, float lux, int motion,
              String status, float lat, float lng) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(SERVER);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String body = "lamp_id=" + String(lampID)
              + "&lux="    + String(lux, 1)
              + "&motion=" + String(motion)
              + "&status=" + status
              + "&lat="    + String(lat, 6)
              + "&lng="    + String(lng, 6);

  http.POST(body);
  http.end();
}


void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("=== Siraj (Motion Only Mode) ===");

  pinMode(PIR1_PIN, INPUT);
  pinMode(PIR2_PIN, INPUT);

  ledcAttach(LED1A_PIN, 5000, 8);
  ledcAttach(LED1B_PIN, 5000, 8);
  ledcAttach(LED2A_PIN, 5000, 8);
  ledcAttach(LED2B_PIN, 5000, 8);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);

  WiFi.begin(SSID, PASSWORD);
}


// ── MAIN LOOP ────────────────────────────────────────────────
void loop() {

  while (gpsSerial.available())
    gps.encode(gpsSerial.read());

  unsigned long now = millis();

  // ── Sensors ───────────────────────────────────────────
  bool pir1 = digitalRead(PIR1_PIN);
  bool pir2 = digitalRead(PIR2_PIN);
  int  ldr1 = analogRead(LDR1_PIN);
  int  ldr2 = analogRead(LDR2_PIN);
  int  pot  = analogRead(POT_PIN);

#define MOTION_COOLDOWN 3000  // ignore أي حركة جديدة لمدة 3 ثواني

if (pir1 && (now - lastMotion1 > MOTION_COOLDOWN)) {
  lastMotion1 = now;
}

if (pir2 && (now - lastMotion2 > MOTION_COOLDOWN)) {
  lastMotion2 = now;
}

  bool motion1    = (now - lastMotion1 < MOTION_TIMEOUT);
  bool motion2    = (now - lastMotion2 < MOTION_TIMEOUT);
  bool manualMode = (pot > POT_THRESHOLD);

  // ── Brightness ────────────────────────────────────────
  int br1, br2;

  if (manualMode) {
    br1 = br2 = map(pot, POT_THRESHOLD, 4095, 0, 255);
  } else {
    br1 = motion1 ? BRIGHT_HIGH : BRIGHT_LOW;
    br2 = motion2 ? BRIGHT_HIGH : BRIGHT_LOW;
  }

  // ── Output ────────────────────────────────────────────
  ledcWrite(LED1A_PIN, br1);
  ledcWrite(LED1B_PIN, br1);
  ledcWrite(LED2A_PIN, br2);
  ledcWrite(LED2B_PIN, br2);

  // ── GPS ───────────────────────────────────────────────
  float gpsLat = gps.location.isValid() ? gps.location.lat() : 0.0;
  float gpsLng = gps.location.isValid() ? gps.location.lng() : 0.0;

  // ── Upload ────────────────────────────────────────────
  if (now - lastUpload >= UPLOAD_INTERVAL) {
    lastUpload = now;
    sendToDB(LAMP_PAIR1, (float)ldr1, motion1 ? 1 : 0,
             br1 > 0 ? "on" : "off", gpsLat, gpsLng);
    sendToDB(LAMP_PAIR2, (float)ldr2, motion2 ? 1 : 0,
             br2 > 0 ? "on" : "off", gpsLat, gpsLng);
  }

  // ── Debug ─────────────────────────────────────────────
  Serial.println("--------------------------------------");
  Serial.printf("PIR1:%-8s | PIR2:%s\n",
    pir1 ? "MOTION" : "clear", pir2 ? "MOTION" : "clear");
  Serial.printf("Brightness P1:%3d | P2:%3d\n", br1, br2);
  Serial.printf("Mode:%s\n", manualMode ? "MANUAL" : "AUTO");

  delay(500);
}
