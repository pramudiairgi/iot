/*
 * =================================================================
 * MONITORING SUHU & KELEMBABAN DENGAN ESP8266, DHT11, LCD, MQTT, DAN API PHP
 * =================================================================
 * Fitur:
 * - Baca data dari DHT11
 * - Tampilkan ke LCD I2C
 * - Publish ke MQTT Broker (format JSON)
 * - Kirim ke API PHP via HTTP POST
 * =================================================================
 */

#include <EEPROM.h>
#include <ESP8266WiFi.h>
#include <PubSubClient.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>
#include <HTTPClient.h>
#include "DHT.h"

// === Konfigurasi DHT dan LCD ===
#define DHTPIN D5
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);
LiquidCrystal_I2C lcd(0x27, 16, 2);  // Ganti ke 0x3F jika I2C tidak terbaca

// === WiFi & MQTT ===
const char* ssid = "JA66AD";
const char* password = "jaggadditanganmu";
const char* mqtt_server = "mqtt.revolusi-it.com";
const int mqtt_port = 1883;
const char* user_mqtt = "usm";
const char* pass_mqtt = "usmjaya1";
const char* topik = "iot/G.231.22.0165";

// === Status Output Pin ===
int stat_D0 = 0, stat_D1 = 0, stat_D2 = 0, stat_D3 = 0, stat_D4 = 0;

// === LED Indikator ===
#define LED_SUHU D6
#define LED_KELEMBABAN D7
#define LED_BAHAYA D8

WiFiClient espClient;
PubSubClient client(espClient);

// === Fungsi: Callback saat MQTT Menerima Pesan ===
void callback(char* topic, byte* payload, unsigned int length) {
  String msg = "";
  for (int i = 0; i < length; i++) msg += (char)payload[i];

  Serial.println("MQTT Pesan Diterima: " + msg);

  if (msg == "D0=1") stat_D0 = 1; if (msg == "D0=0") stat_D0 = 0;
  if (msg == "D1=1") stat_D1 = 1; if (msg == "D1=0") stat_D1 = 0;
  if (msg == "D2=1") stat_D2 = 1; if (msg == "D2=0") stat_D2 = 0;
  if (msg == "D3=1") stat_D3 = 1; if (msg == "D3=0") stat_D3 = 0;
  if (msg == "D4=1") stat_D4 = 1; if (msg == "D4=0") stat_D4 = 0;
}

// === Fungsi: Kirim ke API PHP ===
void kirimKeAPI(float suhu, float kelembaban, String status) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin("http://server.com/insert.php");  // Ganti ke IP atau domain server kamu
    http.addHeader("Content-Type", "application/json");

    String payload = "{\"temperature\":" + String(suhu, 1) + ",\"humidity\":" + String(kelembaban, 1) + ",\"status\":\"" + status + "\"}";

    int httpResponseCode = http.POST(payload);
    Serial.print("HTTP Response: ");
    Serial.println(httpResponseCode);
    http.end();
  } else {
    Serial.println("WiFi Lost, gagal kirim API");
  }
}

// === Fungsi: Kedip LED ===
void kedipLED(int pin, int jumlah) {
  for (int i = 0; i < jumlah; i++) {
    digitalWrite(pin, HIGH);
    delay(200);
    digitalWrite(pin, LOW);
    delay(300);
  }
}

// === Fungsi: Tampilkan ke LCD ===
void tampil_lcd(float suhu, float kelembaban, String status) {
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("S:" + String(suhu, 1) + "C H:" + String(kelembaban, 0) + "%");
  lcd.setCursor(0, 1);
  lcd.print("Status: " + status);
}

// === Fungsi: Baca Sensor dan Buat JSON MQTT ===
String cek_sensor_dan_buat_json(float &suhu, float &kelembaban, String &status_final) {
  float h = dht.readHumidity();
  float t = dht.readTemperature();

  if (isnan(h) || isnan(t)) {
    Serial.println("Gagal baca DHT!");
    return "";
  }

  suhu = t;
  kelembaban = h;
  status_final = "Aman";

  if (t > 31) { kedipLED(LED_SUHU, 3); kedipLED(LED_BAHAYA, 3); status_final = "Tidak Aman"; }
  else if (t >= 30) { kedipLED(LED_SUHU, 2); status_final = "Waspada"; }
  else if (t > 29) { kedipLED(LED_SUHU, 1); status_final = "Waspada"; }

  if (h >= 70) { kedipLED(LED_KELEMBABAN, 3); if (status_final != "Tidak Aman") kedipLED(LED_BAHAYA, 3); status_final = "Tidak Aman"; }
  else if (h >= 60) { kedipLED(LED_KELEMBABAN, 1); if (status_final == "Aman") status_final = "Waspada"; }

  tampil_lcd(t, h, status_final);

  StaticJsonDocument<200> doc;
  doc["temperature"] = t;
  doc["humidity"] = h;
  doc["status"] = status_final;

  String jsonStr;
  serializeJson(doc, jsonStr);

  return jsonStr;
}

// === Fungsi: Eksekusi Pin Output ===
void eksekusi_pin() {
  digitalWrite(D0, stat_D0);
  digitalWrite(D1, stat_D1);
  digitalWrite(D2, stat_D2);
  digitalWrite(D3, stat_D3);
  digitalWrite(D4, stat_D4);
}

// === Fungsi: Koneksi WiFi ===
void konek_wifi() {
  Serial.print("Menyambung WiFi: ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);
  lcd.clear();
  lcd.setCursor(0, 0);
  lcd.print("Connect WiFi...");

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    lcd.print(".");
  }

  Serial.println("\nWiFi Terhubung!");
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
}

// === Fungsi: Reconnect MQTT ===
void reconnect() {
  while (!client.connected()) {
    Serial.println("MQTT Connecting...");
    String clientId = "ESP8266Client-G231220165";
    if (client.connect(clientId.c_str(), user_mqtt, pass_mqtt)) {
      Serial.println("MQTT Connected!");
      client.subscribe(topik);
    } else {
      Serial.print("MQTT Gagal, code: ");
      Serial.print(client.state());
      delay(3000);
    }
  }
}

// === SETUP ===
void setup() {
  Serial.begin(9600);
  Serial.println("ESP Starting...");

  for (int i = D0; i <= D4; i++) pinMode(i, OUTPUT);
  pinMode(LED_SUHU, OUTPUT);
  pinMode(LED_KELEMBABAN, OUTPUT);
  pinMode(LED_BAHAYA, OUTPUT);

  Wire.begin();
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("IoT Monitoring");
  lcd.setCursor(0, 1);
  lcd.print("Starting...");
  delay(3000);

  dht.begin();
  konek_wifi();

  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(callback);
}

// === LOOP ===
void loop() {
  if (WiFi.status() != WL_CONNECTED) konek_wifi();
  if (!client.connected()) reconnect();

  client.loop();
  eksekusi_pin();

  static unsigned long lastMsg = 0;
  if (millis() - lastMsg > 5000) {
    lastMsg = millis();

    float suhu, kelembaban;
    String status;
    String pesan_json = cek_sensor_dan_buat_json(suhu, kelembaban, status);

    if (pesan_json.length() > 0) {
      client.publish(topik, pesan_json.c_str(), true);
      kirimKeAPI(suhu, kelembaban, status);
    }
  }
}
