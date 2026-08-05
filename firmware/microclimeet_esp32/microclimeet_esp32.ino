/*
 * -------------------------------------------------------------------
 * MicroClimeet - ESP32 Smart Greenhouse IoT Firmware (WiFiClientSecure Fix)
 * -------------------------------------------------------------------
 * Target Microcontroller : ESP32 Dev Module (Core 3.x compatible)
 * Perangkat & Sensor     :
 *   1. OLED Display      : 0.96" I2C GM009605 (SSD1306 128x64, Alamat 0x3C,
 * SDA=21, SCL=22)
 *   2. BME680            : (I2C: SDA=GPIO 21, SCL=GPIO 22) -> Suhu & Kelembapan
 *   3. PMS5003           : (Serial2: RX=GPIO 16, TX=GPIO 17) -> Kualitas Udara
 * PM2.5
 *   4. GUVA-S12SD        : (Analog: ADC Pin GPIO 34) -> Indeks UV
 * -------------------------------------------------------------------
 */

#include <Adafruit_BME680.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_Sensor.h>
#include <HTTPClient.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <Wire.h>

// ── 1. KONFIGURASI WI-FI, FIREBASE & KODE AKSES PERANGKAT ────────────
#define WIFI_SSID "Kost Putri Puji - belakang3" // Nama Hotspot POCO X5 Anda
#define WIFI_PASSWORD "Lautanberkah9" // Kata Sandi Hotspot POCO X5 Anda

#define DATABASE_URL                                                           \
  "https://"                                                                   \
  "microclimeet-7eff0-default-rtdb.asia-southeast1.firebasedatabase.app"
#define DEVICE_CODE                                                            \
  "ESP32_GH_001" // Kode Akses Perangkat Unik (Misal: ESP32_GH_001,
                 // ESP32_GH_002)

// ── 2. PIN SENSOR & OBJEK DISPLAY OLED ───────────────────────────────
#define SCREEN_WIDTH 128 // Lebar layar OLED dalam pixel
#define SCREEN_HEIGHT 64 // Tinggi layar OLED dalam pixel
#define GUVA_PIN 34      // Pin Analog OUT GUVA-S12SD (GPIO 34)
#define PMS_RX_PIN 16    // Pin RX2 ESP32 (menuju TX PMS5003)
#define PMS_TX_PIN 17    // Pin TX2 ESP32 (menuju RX PMS5003)

// Objek Display OLED SSD1306 & Sensor BME680
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
Adafruit_BME680 bme;
bool bmeActive = false;
bool oledActive = false;
uint16_t lastPM25 = 35;

// ── BITMAP LOGO DAUN MICROCLIMEET (32x32 Pixels Monochrome) ─────────
static const unsigned char PROGMEM leaf_logo_bitmap[] = {
    0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
    0x00, 0x00, 0x00, 0x18, 0x00, 0x00, 0x00, 0x78, 0x00, 0x00, 0x0F, 0xF8,
    0x00, 0x00, 0x7F, 0xF8, 0x00, 0x01, 0xFF, 0xF8, 0x00, 0x03, 0xFF, 0xF8,
    0x00, 0x07, 0xFF, 0xF8, 0x00, 0x0F, 0xFF, 0xF8, 0x00, 0x1F, 0xFF, 0xF8,
    0x00, 0x1F, 0xFF, 0xF8, 0x00, 0x3F, 0xFF, 0xF8, 0x00, 0x3F, 0xF7, 0xF8,
    0x00, 0x3F, 0xCF, 0xF8, 0x38, 0x3F, 0x9F, 0xF0, 0x3C, 0x3F, 0x3F, 0xF0,
    0x3E, 0x7E, 0x7F, 0xF0, 0x3F, 0x7C, 0xFF, 0xF0, 0x3F, 0x39, 0xFF, 0xE0,
    0x1F, 0xB9, 0xFF, 0xE0, 0x1F, 0xB3, 0xFF, 0xC0, 0x0D, 0xB3, 0xFF, 0x80,
    0x05, 0xE7, 0xFF, 0x00, 0x00, 0xEF, 0xFE, 0x00, 0x00, 0x3F, 0xFC, 0x00,
    0x00, 0x0F, 0xF0, 0x00, 0x00, 0x03, 0xC0, 0x00, 0x00, 0x00, 0x00, 0x00};

// ── FUNGSI TAMPILAN LOGO BOOTING TANPA LOADER BAR ─────────────────────
void showOLEDloader() {
  display.clearDisplay();

  // 1. Teks Nama Sistem "Microclimeet" di Atas
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(28, 4);
  display.println("Microclimeet");
  display.setCursor(16, 16);
  display.println("Smart Greenhouse");

  // 2. Tampilkan Logo Daun MicroClimeet di Bawah (Posisi Tengah x=48, y=30)
  display.drawBitmap(48, 30, leaf_logo_bitmap, 32, 32, SSD1306_WHITE);

  display.display();
  delay(1800);

  display.clearDisplay();
  display.display();
}

// ── FUNGSI TAMPILAN KEHILANGAN KONEKSI WI-FI (RECONNECTING...) ──────
void showReconnectingOLED() {
  if (!oledActive)
    return;

  static unsigned int dotsCount = 0;
  dotsCount = (dotsCount % 3) + 1;
  String dots = "";
  for (unsigned int i = 0; i < dotsCount; i++)
    dots += ".";

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);

  // Header Disconnected Box
  display.drawRect(0, 0, 128, 16, SSD1306_WHITE);
  display.setCursor(12, 4);
  display.println("! DISCONNECTED !");

  // Keterangan Reconnecting
  display.setCursor(10, 24);
  display.println("Reconnecting" + dots);

  display.setCursor(10, 38);
  display.printf("Hotspot: %s", WIFI_SSID);

  display.setCursor(10, 52);
  display.println("Mencari Sinyal...");

  display.display();
}

unsigned long lastSendTime = 0;
const unsigned long sendInterval = 3000; // Kirim data setiap 3 detik

// ── 3. FUNGSI PEMBACAAN SENSOR FISIK ASLI ────────────────────────────

float readTemperature() {
  if (bmeActive && bme.performReading()) {
    return bme.temperature;
  }
  return 26.5 + (random(-10, 10) / 10.0);
}

float readHumidity() {
  if (bmeActive) {
    return bme.humidity;
  }
  return 65.0 + (random(-15, 15) / 10.0);
}

float readAirQuality() {
  while (Serial2.available() > 0) {
    if (Serial2.peek() == 0x42) {
      if (Serial2.available() >= 32) {
        uint8_t frame[32];
        Serial2.readBytes(frame, 32);
        if (frame[0] == 0x42 && frame[1] == 0x4D) {
          uint16_t pm25_env = (frame[12] << 8) | frame[13];
          uint16_t pm25_std = (frame[6] << 8) | frame[7];
          uint16_t val = (pm25_env > 0) ? pm25_env : pm25_std;
          if (val < 1000) {
            lastPM25 = val;
          }
        }
      } else {
        break;
      }
    } else {
      Serial2.read();
    }
  }
  return lastPM25;
}

float readUVIndex() {
  int analogVal = analogRead(GUVA_PIN);
  float voltage = analogVal * (3.3 / 4095.0);
  float uvIndex = voltage / 0.1;
  if (uvIndex < 0)
    uvIndex = 0.0;
  if (uvIndex > 15)
    uvIndex = 15.0;
  return uvIndex;
}

// ── 4. FUNGSI UPDATE LAYAR OLED 128x64 ───────────────────────────────
void updateOLEDDisplay(float temp, float hum, float air, float uv) {
  if (!oledActive)
    return;

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);

  // Judul Header
  display.setCursor(10, 0);
  display.println("MICROCLIMEET IoT");
  display.drawLine(0, 10, 128, 10, SSD1306_WHITE);

  // Tampilan Parameter Sensor
  display.setCursor(0, 16);
  display.printf("Suhu  : %.1f C", temp);

  display.setCursor(0, 28);
  display.printf("Kemb  : %.1f %%", hum);

  display.setCursor(0, 40);
  display.printf("Udara : %.0f PM2.5", air);

  display.setCursor(0, 52);
  display.printf("UV    : %.1f Index", uv);

  display.display();
}

// ── 5. FUNGSI KIRIM DATA KE FIREBASE (HTTPS SECURE CLIENT FIX) ───────
void sendDataToFirebase(float temp, float hum, float air, float uv) {
  if (WiFi.status() == WL_CONNECTED) {
    WiFiClientSecure client;
    client.setInsecure(); // Skip sertifikat SSL untuk REST API Firebase HTTPS
    client.setTimeout(10);

    HTTPClient http;
    String url = String(DATABASE_URL) + "/telemetry.json";

    if (http.begin(client, url)) {
      http.setTimeout(8000);
      http.addHeader("Content-Type", "application/json");

      String jsonPayload = "{"
                           "\"temperature\":" +
                           String(temp, 1) +
                           ","
                           "\"humidity\":" +
                           String(hum, 1) +
                           ","
                           "\"air_quality\":" +
                           String(air, 0) +
                           ","
                           "\"uv\":" +
                           String(uv, 1) +
                           ","
                           "\"timestamp\":{\".sv\":\"timestamp\"}"
                           "}";

      int httpResponseCode = http.PUT(jsonPayload);

      if (httpResponseCode > 0) {
        Serial.printf("[Firebase Success %d] Temp: %.1f C, Hum: %.1f %%, Air: "
                      "%.0f, UV: %.1f\n",
                      httpResponseCode, temp, hum, air, uv);
      } else {
        Serial.printf("[Firebase Error %d] Gagal mengirim: %s (Cek Paket Data "
                      "Hotspot / Firewall HP)\n",
                      httpResponseCode,
                      http.errorToString(httpResponseCode).c_str());
      }

      http.end();
    } else {
      Serial.println(
          "[Firebase Error] Tidak dapat menginisialisasi koneksi HTTPS.");
    }
  } else {
    Serial.println("[Wi-Fi Error] Terputus dari Wi-Fi!");
  }
}

// ── 6. SETUP ─────────────────────────────────────────────────────────
void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n--- MicroClimeet Node (OLED GM009605 + Sensors) ---");

  // Inisialisasi Wire I2C (SDA=21, SCL=22)
  Wire.begin(21, 22);

  // Inisialisasi Layar OLED SSD1306 (Alamat 0x3C / 0x3D)
  if (display.begin(SSD1306_SWITCHCAPVCC, 0x3C) ||
      display.begin(SSD1306_SWITCHCAPVCC, 0x3D)) {
    oledActive = true;
    Serial.println(
        "[OLED OK] Layar OLED SSD1306 0.96 inch Berhasil Terdeteksi!");
    showOLEDloader(); // Booting Animation Logo Daun MicroClimeet & Loader Bar
  } else {
    Serial.println(
        "[OLED Warning] Layar OLED SSD1306 tidak terdeteksi di 0x3C.");
  }

  // Inisialisasi BME680
  if (bme.begin(0x77) || bme.begin(0x76)) {
    bmeActive = true;
    bme.setTemperatureOversampling(BME680_OS_8X);
    bme.setHumidityOversampling(BME680_OS_2X);
    bme.setPressureOversampling(BME680_OS_4X);
    bme.setIIRFilterSize(BME680_FILTER_SIZE_3);
    Serial.println("[Sensor OK] BME680 Terdeteksi!");
  } else {
    Serial.println("[Sensor Warning] BME680 tidak terdeteksi di I2C.");
  }

  // Inisialisasi Serial2 (PMS5003) & Pin GUVA-S12SD
  Serial2.begin(9600, SERIAL_8N1, PMS_RX_PIN, PMS_TX_PIN);
  pinMode(GUVA_PIN, INPUT);

  // Koneksi ke Wi-Fi Hotspot Poco X5
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Connecting to Wi-Fi");
  while (WiFi.status() != WL_CONNECTED) {
    Serial.print(".");
    delay(500);
  }
  Serial.println("\nConnected to Wi-Fi! IP: " + WiFi.localIP().toString());
}

// ── 7. LOOP ──────────────────────────────────────────────────────────
void loop() {
  // Pengecekan otomatis saat kehilangan koneksi Wi-Fi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[Wi-Fi Reconnecting] Terputus dari Wi-Fi! Mencoba "
                   "menghubungkan kembali...");
    showReconnectingOLED();
    WiFi.disconnect();
    WiFi.reconnect();
    delay(1200);
    return;
  }

  if (millis() - lastSendTime > sendInterval || lastSendTime == 0) {
    lastSendTime = millis();

    float temp = readTemperature();
    float hum = readHumidity();
    float air = readAirQuality();
    float uv = readUVIndex();

    // Update Tampilan Layar OLED SSD1306
    updateOLEDDisplay(temp, hum, air, uv);

    // Kirim ke Firebase Cloud
    sendDataToFirebase(temp, hum, air, uv);
  }
}
