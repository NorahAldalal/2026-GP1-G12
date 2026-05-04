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
#define LDR1_PIN   32   // حساس ضوء اللمبة 1
#define LDR2_PIN   33   // حساس ضوء اللمبة 2
#define LED1A_PIN  18
#define LED1B_PIN  19
#define LED2A_PIN  26
#define LED2B_PIN  14
#define POT_PIN    36
#define GPS_RX     16
#define GPS_TX     17

// ── Brightness ───────────────────────────────────────────────
#define POT_THRESHOLD   1200  // فوق هذا = manual mode
#define BRIGHT_HIGH      150  // عند وجود حركة
#define BRIGHT_LOW         40  // بدون حركة = مطفي
#define BRIGHT_OFF         5  // مطفي تماماً
#define COMP_BOOST        75  // إضافة على اللمبة السليمة عند عطل الأخرى
#define MOTION_TIMEOUT  30000 // ms — دقيقة بعد آخر حركة

// ── LDR Fault Detection ──────────────────────────────────────
// إذا اللمبة مفروض تكون مضيئة لكن الـ LDR يقرأ قيمة منخفضة = عطل
// القيمة المنخفضة تعني ضوء قليل = اللمبة مو شغّالة
#define LDR_FAULT_THRESHOLD  100  // إذا القراءة أقل من هذا = عطل

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

  int code = http.POST(body);
  Serial.printf("DB [Lamp%d] HTTP:%d\n", lampID, code);
  http.end();

  // Insert reading
HTTPClient http2;
http2.begin("http://192.168.100.158/Siraj/api/insert_reading.php");
http2.addHeader("Content-Type", "application/x-www-form-urlencoded");
String body2 = "lamp_id="        + String(lampID)
             + "&ambientLight="   + String(lux, 1)
             + "&motionDetected=" + String(motion);
int code2 = http2.POST(body2);
Serial.printf("Reading [Lamp%d] HTTP:%d\n", lampID, code2);
http2.end();
}


void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("=== Siraj (LDR Fault Detection) ===");

  pinMode(PIR1_PIN, INPUT);
  pinMode(PIR2_PIN, INPUT);

  ledcAttach(LED1A_PIN, 5000, 8);
  ledcAttach(LED1B_PIN, 5000, 8);
  ledcAttach(LED2A_PIN, 5000, 8);
  ledcAttach(LED2B_PIN, 5000, 8);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);

  Serial.print("Connecting WiFi");
  WiFi.begin(SSID, PASSWORD);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 20) {
    delay(500); Serial.print("."); tries++;
  }

  if (WiFi.status() == WL_CONNECTED)
    Serial.printf("\nWiFi OK - IP: %s\n", WiFi.localIP().toString().c_str());
  else
    Serial.println("\nWiFi FAILED");

  Serial.println("Ready!");
}


// ── Detect fault using LDR ────────────────────────────────────
// اللمبة عاطلة إذا: المفروض تكون شغّالة (brightness > 0)
// لكن الـ LDR يقرأ ضوء أقل من الحد = ما في ضوء = عطل
bool isFaulty(int brightness, int ldrValue) {
  if (brightness == BRIGHT_OFF) return false; // مو مفروض تشتغل أصلاً
  return (ldrValue < LDR_FAULT_THRESHOLD);    // مفروض تشتغل لكن ما في ضوء
}


void loop() {
  // ── Read GPS ───────────────────────────────────────────
  while (gpsSerial.available())
    gps.encode(gpsSerial.read());

  unsigned long now = millis();

  // ── Read sensors ───────────────────────────────────────
  bool pir1 = digitalRead(PIR1_PIN);
  bool pir2 = digitalRead(PIR2_PIN);
  int  ldr1 = analogRead(LDR1_PIN);  // قراءة حساس ضوء اللمبة 1
  int  ldr2 = analogRead(LDR2_PIN);  // قراءة حساس ضوء اللمبة 2
  int  pot  = analogRead(POT_PIN);

  if (pir1) lastMotion1 = now;
  if (pir2) lastMotion2 = now;

  bool motion1    = (now - lastMotion1 < MOTION_TIMEOUT);
  bool motion2    = (now - lastMotion2 < MOTION_TIMEOUT);
  bool manualMode = (pot > POT_THRESHOLD);

  // ── Base brightness ────────────────────────────────────
  int br1, br2;

  if (manualMode) {
    br1 = br2 = map(pot, POT_THRESHOLD, 4095, 0, 255);
  } else {
    br1 = motion1 ? BRIGHT_HIGH : BRIGHT_LOW;
    br2 = motion2 ? BRIGHT_HIGH : BRIGHT_LOW;
  }

  // ── Fault detection via LDR ────────────────────────────
  bool fault1 = !manualMode && isFaulty(br1, ldr1);
  bool fault2 = !manualMode && isFaulty(br2, ldr2);

  // ── Compensation logic ─────────────────────────────────
  if (!manualMode) {
    if (fault1 && !fault2) {
      // اللمبة 1 عاطلة → نطفيها ونعوّض باللمبة 2
      br1 = BRIGHT_OFF;
      br2 = min(255, br2 + COMP_BOOST);
    } else if (fault2 && !fault1) {
      // اللمبة 2 عاطلة → نطفيها ونعوّض باللمبة 1
      br2 = BRIGHT_OFF;
      br1 = min(255, br1 + COMP_BOOST);
    } else if (fault1 && fault2) {
      br1 = BRIGHT_OFF;
      br2 = BRIGHT_OFF;
    }
  }

  // ── Write PWM ──────────────────────────────────────────
  ledcWrite(LED1A_PIN, br1);
  ledcWrite(LED1B_PIN, br1);
  ledcWrite(LED2A_PIN, br2);
  ledcWrite(LED2B_PIN, br2);

  // ── GPS ────────────────────────────────────────────────
  float gpsLat = gps.location.isValid() ? gps.location.lat() : 0.0;
  float gpsLng = gps.location.isValid() ? gps.location.lng() : 0.0;

  // ── Upload every 10 seconds ────────────────────────────
  if (now - lastUpload >= UPLOAD_INTERVAL) {
    lastUpload = now;
     Serial.println(">>> Sending to DB..."); 
    sendToDB(LAMP_PAIR1, (float)ldr1, motion1 ? 1 : 0,
             br1 > 0 ? "on" : "off", gpsLat, gpsLng);
    sendToDB(LAMP_PAIR2, (float)ldr2, motion2 ? 1 : 0,
             br2 > 0 ? "on" : "off", gpsLat, gpsLng);
  }

  // ── Serial Monitor ─────────────────────────────────────
  Serial.println("--------------------------------------");
  Serial.printf("PIR1:%-8s | PIR2:%s\n",
    pir1 ? "MOTION" : "clear", pir2 ? "MOTION" : "clear");
  Serial.printf("LDR1:%4d | LDR2:%4d\n", ldr1, ldr2);
  Serial.printf("Fault1:%-4s | Fault2:%s\n",
    fault1 ? "YES" : "no", fault2 ? "YES" : "no");
  Serial.printf("POT:%4d | Mode:%s\n",
    pot, manualMode ? "MANUAL" : "AUTO");
  Serial.printf("Brightness P1:%3d | P2:%3d\n", br1, br2);

  if (!manualMode) {
    if      (fault1 && !fault2) Serial.println("⚠  LAMP1 FAULT → P2 compensating");
    else if (fault2 && !fault1) Serial.println("⚠  LAMP2 FAULT → P1 compensating");
    else if (fault1 && fault2)  Serial.println("⚠  BOTH LAMPS FAULT");
    else                        Serial.println("✓  Both lamps OK");
  } else {
    Serial.println(">> MANUAL mode");
  }

  Serial.printf("GPS: %s | Sats:%d\n",
    gps.location.isValid()
      ? (String("Lat=") + String(gpsLat, 6) + " Lng=" + String(gpsLng, 6)).c_str()
      : "Searching...",
    gps.satellites.value());
  Serial.printf("WiFi: %s\n",
    WiFi.status() == WL_CONNECTED
      ? WiFi.localIP().toString().c_str() : "disconnected");

  delay(500);
}
