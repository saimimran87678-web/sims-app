/*
 ==============================================================================
  Adminova SIMS — Autonomous Edge IoT Smart Card Reader & Gate Terminal
  Firmware Version: 2.6.1 (Dual-Mode: Attendance Scanner + Card Provisioner)
  Target Hardware: NodeMCU v3 / D1 Mini (ESP8266)
 ==============================================================================
  Updated Wiring Diagram:
    RC-522 PIN      NodeMCU PIN      Notes
    -----------------------------------------------------
    VCC             3.3V             CRITICAL: Do NOT use 5V!
    RST             D0 (GPIO 16)     Hardware Reset (Moved from D3)
    GND             GND              Ground
    MISO            D6 (GPIO 12)     Hardware SPI MISO
    MOSI            D7 (GPIO 13)     Hardware SPI MOSI
    SCK             D5 (GPIO 14)     Hardware SPI SCK
    SDA (SS / CS)   D8 (GPIO 15)     Hardware SPI CS (Moved from D4)

    1.3" OLED PIN   NodeMCU PIN      Notes
    -----------------------------------------------------
    VCC             3.3V             Power
    GND             GND              Ground
    SCL             D1 (GPIO 5)      Hardware I2C Clock
    SDA             D2 (GPIO 4)      Hardware I2C Data
 ==============================================================================
*/

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <U8g2lib.h>
#include <ArduinoJson.h>
#include <FS.h>

// ==========================================
// 1. CONFIGURATION: Wi-Fi & SIMS Server
// ==========================================
const char* WIFI_SSID     = "Tenda_049520";
const char* WIFI_PASSWORD = "51005200";
const char* SERVER_HOST   = "http://192.168.0.105";
const char* DEVICE_ID     = "GATE-01-MAIN";
const char* DEVICE_SECRET = "saim@123";

// ==========================================
// 2. PIN DEFINITIONS (NodeMCU ESP8266 GPIO Map)
// ==========================================
#ifndef D0
  #define D0 16
#endif
#ifndef D1
  #define D1 5
#endif
#ifndef D2
  #define D2 4
#endif
#ifndef D3
  #define D3 0
#endif
#ifndef D4
  #define D4 2
#endif
#ifndef D5
  #define D5 14
#endif
#ifndef D6
  #define D6 12
#endif
#ifndef D7
  #define D7 13
#endif
#ifndef D8
  #define D8 15
#endif

// Reassigned to stable non-strapping hardware pins
#define SS_PIN          D8   // GPIO 15 -> RC-522 Chip Select (SDA/SS pin on reader)
#define RST_PIN         D0   // GPIO 16 -> RC-522 Reset (Stable low-impedance reset)
#define OLED_SCL_PIN    D1   // GPIO 5  -> I2C Clock (SCL pin on OLED)
#define OLED_SDA_PIN    D2   // GPIO 4  -> I2C Data (SDA pin on OLED)

// Initialize RC-522 RFID
MFRC522 mfrc522(SS_PIN, RST_PIN);
MFRC522::MIFARE_Key key;

// Initialize 1.3" OLED (SH1106 I2C 128x64 noname)
// If your display uses SSD1306, switch to: U8G2_SSD1306_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, U8X8_PIN_NONE);
U8G2_SH1106_128X64_NONAME_F_HW_I2C u8g2(U8G2_R0, /* reset=*/ U8X8_PIN_NONE);

// Debounce Tracking
String lastCardUid = "";
unsigned long lastScanMillis = 0;
const unsigned long DEBOUNCE_DELAY_MS = 2500;

// Hardware Health & Self-Test State
bool rfidHardwareOk = false;
String rfidVersionDesc = "Unknown";

// Function: Verify RC-522 SPI connection by checking Version register
bool verifyRfidHardware(String &versionName) {
  byte ver = mfrc522.PCD_ReadRegister(mfrc522.VersionReg);
  char hexBuf[10];
  sprintf(hexBuf, "0x%02X", ver);

  if (ver == 0x00 || ver == 0xFF) {
    versionName = "COMM_FAIL (" + String(hexBuf) + ")";
    return false;
  } else if (ver == 0x92) {
    versionName = "v2.0 (0x92 NXP)";
    return true;
  } else if (ver == 0x91) {
    versionName = "v1.0 (0x91)";
    return true;
  } else if (ver == 0x88) {
    versionName = "FM17522 (0x88 Clone)";
    return true;
  } else if (ver == 0x12) {
    versionName = "v0.0 (0x12 Counterfeit)";
    return true;
  } else {
    versionName = "Chip (" + String(hexBuf) + ")";
    return true;
  }
}

// ==========================================
// 3. DISPLAY HELPER FUNCTIONS
// ==========================================
void showScreenIdle() {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(12, 12, "ADMINOVA SIMS");
  u8g2.drawHLine(0, 16, 128);

  u8g2.setFont(u8g2_font_6x10_tr);
  if (rfidHardwareOk) {
    u8g2.drawStr(16, 33, "[[ TAP SMART CARD ]]");
  } else {
    u8g2.drawStr(8, 33, "! SENSOR OFFLINE !");
  }

  u8g2.setFont(u8g2_font_5x7_tr);
  String ipStr = "LAN: " + WiFi.localIP().toString();
  u8g2.drawStr(0, 52, ipStr.c_str());

  String devStr = "ID: " + String(DEVICE_ID);
  u8g2.drawStr(0, 62, devStr.c_str());

  if (rfidHardwareOk) {
    u8g2.drawStr(82, 62, "RC522: OK");
  } else {
    u8g2.drawStr(78, 62, "RC522: ERR");
  }
  u8g2.sendBuffer();
}

void showScreenScanning() {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(24, 25, "CARD DETECTED");
  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(18, 45, "Verifying SIMS...");
  u8g2.sendBuffer();
}

void showScreenAttendanceSuccess(const char* name, const char* roll, const char* cls, const char* timeStr) {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(4, 12, "WELCOME  [ PRESENT ]");
  u8g2.drawHLine(0, 15, 128);

  u8g2.setFont(u8g2_font_7x14B_tr);
  u8g2.drawStr(4, 32, name);

  u8g2.setFont(u8g2_font_6x10_tr);
  String info = "Roll #" + String(roll) + " - " + String(cls);
  u8g2.drawStr(4, 47, info.c_str());

  String t = "Time: " + String(timeStr) + "  OK";
  u8g2.drawStr(4, 60, t.c_str());
  u8g2.sendBuffer();
}

void showScreenAlreadyMarked(const char* name, const char* timeStr) {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(12, 12, "ALREADY RECORDED");
  u8g2.drawHLine(0, 15, 128);

  u8g2.setFont(u8g2_font_7x14B_tr);
  u8g2.drawStr(4, 32, name);

  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(4, 48, "Check-in was at:");
  u8g2.drawStr(4, 60, timeStr);
  u8g2.sendBuffer();
}

void showScreenCardWritten(const char* name, const char* roll, const char* cls) {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(4, 12, "CARD WRITTEN! [ OK ]");
  u8g2.drawHLine(0, 15, 128);

  u8g2.setFont(u8g2_font_7x14B_tr);
  u8g2.drawStr(4, 32, name);

  u8g2.setFont(u8g2_font_6x10_tr);
  String info = "Roll: " + String(roll) + " (" + String(cls) + ")";
  u8g2.drawStr(4, 47, info.c_str());

  u8g2.setFont(u8g2_font_5x7_tr);
  u8g2.drawStr(4, 60, "Crypto HMAC Block Sealed");
  u8g2.sendBuffer();
}

void showScreenInspecting() {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(14, 14, "INSPECTING CARD");
  u8g2.drawHLine(0, 18, 128);

  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(12, 36, "Reading EEPROM...");
  u8g2.setFont(u8g2_font_5x7_tr);
  u8g2.drawStr(12, 52, "Verifying HMAC Seal");
  u8g2.sendBuffer();
}

void showScreenInspectSuccess(const char* name, bool hmacValid) {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(12, 12, "CHIP INSPECTION");
  u8g2.drawHLine(0, 15, 128);

  u8g2.setFont(u8g2_font_7x14B_tr);
  u8g2.drawStr(4, 32, name);

  u8g2.setFont(u8g2_font_6x10_tr);
  if (hmacValid) {
    u8g2.drawStr(4, 48, "HMAC SEAL: VALID [OK]");
  } else {
    u8g2.drawStr(4, 48, "HMAC: UNSEALED / CLONE");
  }

  u8g2.setFont(u8g2_font_5x7_tr);
  u8g2.drawStr(4, 60, "Report Synced to SIMS");
  u8g2.sendBuffer();
}

void showScreenError(const char* line1, const char* line2) {
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(16, 12, "SECURITY / ALERT");
  u8g2.drawHLine(0, 15, 128);

  u8g2.setFont(u8g2_font_6x10_tr);
  u8g2.drawStr(4, 35, line1);
  u8g2.drawStr(4, 52, line2);
  u8g2.sendBuffer();
}

// ==========================================
// 4. SETUP
// ==========================================
void setup() {
  Serial.begin(115200);

  // Assert Chip Select HIGH before starting SPI to prevent bus contention
  pinMode(SS_PIN, OUTPUT);
  digitalWrite(SS_PIN, HIGH);

  SPI.begin();
  Wire.begin(OLED_SDA_PIN, OLED_SCL_PIN);

  // Initialize 1.3" OLED
  u8g2.begin();
  u8g2.clearBuffer();
  u8g2.setFont(u8g2_font_helvB08_tr);
  u8g2.drawStr(12, 25, "BOOTING SIMS IOT");
  u8g2.setFont(u8g2_font_5x7_tr);
  u8g2.drawStr(12, 45, "Init RC-522 & Wi-Fi...");
  u8g2.sendBuffer();

  // Reset pulse & power settling for RC-522
  pinMode(RST_PIN, OUTPUT);
  digitalWrite(RST_PIN, LOW);
  delay(10);
  digitalWrite(RST_PIN, HIGH);
  delay(50);

  // Initialize RC-522
  mfrc522.PCD_Init();
  delay(50);

  for (byte i = 0; i < 6; i++) {
    key.keyByte[i] = 0xFF; // Default factory key A
  }

  // Active Hardware Self-Test: Read Version register
  rfidHardwareOk = verifyRfidHardware(rfidVersionDesc);
  Serial.print(F("[INIT] RC-522 RFID Sensor Check: "));
  Serial.println(rfidVersionDesc);

  if (!rfidHardwareOk) {
    Serial.println(F("================================================================"));
    Serial.println(F("[CRITICAL ERROR] RC-522 RFID SENSOR NOT DETECTED!"));
    Serial.println(F("PCD_ReadRegister(VersionReg) returned 0x00 or 0xFF."));
    Serial.println(F("Verify your updated SPI jumper wiring:"));
    Serial.println(F("  - RC522 VCC  --> NodeMCU 3.3V  (WARNING: Do NOT connect to 5V!)"));
    Serial.println(F("  - RC522 RST  --> NodeMCU D0 (GPIO 16)"));
    Serial.println(F("  - RC522 GND  --> NodeMCU GND"));
    Serial.println(F("  - RC522 MISO --> NodeMCU D6 (GPIO 12)"));
    Serial.println(F("  - RC522 MOSI --> NodeMCU D7 (GPIO 13)"));
    Serial.println(F("  - RC522 SCK  --> NodeMCU D5 (GPIO 14)"));
    Serial.println(F("  - RC522 SDA  --> NodeMCU D8 (GPIO 15) [SS / Chip Select]"));
    Serial.println(F("================================================================"));

    int retryCount = 0;
    while (!rfidHardwareOk && retryCount < 5) {
      showScreenError("RC-522 NOT FOUND", "Check SPI Wires");
      delay(1200);
      mfrc522.PCD_Init();
      delay(50);
      rfidHardwareOk = verifyRfidHardware(rfidVersionDesc);
      if (rfidHardwareOk) {
        Serial.println(F("[RECOVERED] RC-522 detected and initialized successfully!"));
        break;
      }
      retryCount++;
    }
  } else {
    Serial.println(F("[OK] RC-522 hardware verified and ready."));
  }

  // Initialize LittleFS / SPIFFS Offline Storage
  SPIFFS.begin();

  // Connect to Local Wi-Fi
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 25) {
    delay(300);
    Serial.print(".");
    retries++;
  }

  Serial.println("");
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("WiFi connected. IP: " + WiFi.localIP().toString());
    sendHeartbeat();
  } else {
    Serial.println("WiFi not found. Operating in Offline Fallback Mode.");
  }

  showScreenIdle();
}

// ==========================================
// 5. HEARTBEAT & STATUS
// ==========================================
unsigned long lastHeartbeatMillis = 0;
const unsigned long HEARTBEAT_INTERVAL_MS = 15000;

void sendHeartbeat() {
  if (WiFi.status() != WL_CONNECTED) return;
  WiFiClient client;
  HTTPClient http;
  String url = String(SERVER_HOST) + "/api/v1/rfid/status?device_id=" + String(DEVICE_ID)
             + "&rfid_status=" + (rfidHardwareOk ? "ok" : "error")
             + "&rfid_version=" + (rfidHardwareOk ? "v2.0" : "disconnected");
  http.begin(client, url);
  int httpCode = http.GET();
  if (httpCode == 200) {
    Serial.println("[HEARTBEAT] Pinged SIMS. Device & Sensor status: ONLINE (" + rfidVersionDesc + ").");
  }
  http.end();
}

// ==========================================
// 6. MULTI-SECTOR READ & WRITE LOGIC
// ==========================================
String bytesToHex(const byte* buffer, size_t length) {
  String hexStr = "";
  for (size_t i = 0; i < length; i++) {
    if (buffer[i] < 0x10) hexStr += "0";
    hexStr += String(buffer[i], HEX);
  }
  hexStr.toUpperCase();
  return hexStr;
}

// Write 48 bytes across Blocks 4, 5, and 6 (Sector 1 data blocks)
bool writeCardSectors(byte startBlock, const char* fullHexData) {
  MFRC522::StatusCode status;
  int hexLen = strlen(fullHexData);

  // Authenticate Sector 1 using Key A on startBlock (Block 4)
  status = mfrc522.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A, startBlock, &key, &(mfrc522.uid));
  if (status != MFRC522::STATUS_OK) {
    Serial.print(F("[WRITE ERR] PCD_Authenticate() failed: "));
    Serial.println(mfrc522.GetStatusCodeName(status));
    return false;
  }

  // Write 3 consecutive blocks (Blocks 4, 5, 6)
  for (int b = 0; b < 3; b++) {
    byte blockAddr = startBlock + b;
    byte buffer[16];

    for (int i = 0; i < 16; i++) {
      int hexIndex = (b * 16 + i) * 2;
      if (hexIndex + 1 < hexLen) {
        char byteChars[3] = { fullHexData[hexIndex], fullHexData[hexIndex + 1], '\0' };
        buffer[i] = (byte) strtol(byteChars, NULL, 16);
      } else {
        buffer[i] = 0x00;
      }
    }

    status = mfrc522.MIFARE_Write(blockAddr, buffer, 16);
    if (status != MFRC522::STATUS_OK) {
      Serial.print(F("[WRITE ERR] MIFARE_Write() failed on block "));
      Serial.print(blockAddr);
      Serial.print(F(": "));
      Serial.println(mfrc522.GetStatusCodeName(status));
      return false;
    }
  }

  return true;
}

// Read 48 bytes across Blocks 4, 5, and 6 (Sector 1 data blocks)
bool readCardSectors(byte startBlock, byte numBlocks, byte* outputBuffer) {
  MFRC522::StatusCode status;

  // Authenticate Sector 1 using Key A on startBlock (Block 4)
  status = mfrc522.PCD_Authenticate(MFRC522::PICC_CMD_MF_AUTH_KEY_A, startBlock, &key, &(mfrc522.uid));
  if (status != MFRC522::STATUS_OK) {
    Serial.print(F("[READ ERR] PCD_Authenticate() failed: "));
    Serial.println(mfrc522.GetStatusCodeName(status));
    return false;
  }

  for (byte b = 0; b < numBlocks; b++) {
    byte blockAddr = startBlock + b;
    byte tempBuffer[18];
    byte byteCount = sizeof(tempBuffer);

    status = mfrc522.MIFARE_Read(blockAddr, tempBuffer, &byteCount);
    if (status != MFRC522::STATUS_OK) {
      Serial.print(F("[READ ERR] MIFARE_Read() failed on block "));
      Serial.print(blockAddr);
      Serial.print(F(": "));
      Serial.println(mfrc522.GetStatusCodeName(status));
      return false;
    }

    memcpy(outputBuffer + (b * 16), tempBuffer, 16);
  }

  return true;
}

// ==========================================
// 7. MAIN LOOP
// ==========================================
void loop() {
  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
  }

  unsigned long currentMillis = millis();
  if (currentMillis - lastHeartbeatMillis >= HEARTBEAT_INTERVAL_MS) {
    lastHeartbeatMillis = currentMillis;
    sendHeartbeat();
  }

  // Hot-plug recheck if sensor dropped
  if (!rfidHardwareOk) {
    mfrc522.PCD_Init();
    delay(20);
    if (verifyRfidHardware(rfidVersionDesc)) {
      rfidHardwareOk = true;
      Serial.println(F("[HOT-PLUG] RC-522 RFID sensor reconnected!"));
      showScreenIdle();
    } else {
      delay(200);
      return;
    }
  }

  // Check for card
  if (!mfrc522.PICC_IsNewCardPresent()) {
    delay(50);
    return;
  }
  if (!mfrc522.PICC_ReadCardSerial()) {
    delay(50);
    return;
  }

  // Extract Formatted Card UID
  String cardUid = "";
  for (byte i = 0; i < mfrc522.uid.size; i++) {
    if (mfrc522.uid.uidByte[i] < 0x10) cardUid += "0";
    cardUid += String(mfrc522.uid.uidByte[i], HEX);
    if (i < mfrc522.uid.size - 1) cardUid += ":";
  }
  cardUid.toUpperCase();

  // Debounce duplicate reads
  unsigned long now = millis();
  if (cardUid == lastCardUid && (now - lastScanMillis < DEBOUNCE_DELAY_MS)) {
    mfrc522.PICC_HaltA();
    mfrc522.PCD_StopCrypto1();
    return;
  }
  lastCardUid = cardUid;
  lastScanMillis = now;

  showScreenScanning();

  if (WiFi.status() == WL_CONNECTED) {
    WiFiClient client;
    HTTPClient http;

    String endpoint = String(SERVER_HOST) + "/api/v1/rfid/scan";
    http.begin(client, endpoint);
    http.addHeader("Content-Type", "application/json");

    StaticJsonDocument<512> requestDoc;
    requestDoc["device_id"] = DEVICE_ID;
    requestDoc["device_secret"] = DEVICE_SECRET;
    requestDoc["card_uid"] = cardUid;

    String requestBody;
    serializeJson(requestDoc, requestBody);

    int httpResponseCode = http.POST(requestBody);
    String responseString = http.getString();
    http.end();

    if (httpResponseCode > 0) {
      StaticJsonDocument<1024> resDoc;
      DeserializationError err = deserializeJson(resDoc, responseString);

      if (!err) {
        const char* status = resDoc["status"] | "";
        const char* action = resDoc["action"] | "";

        // ----------------------------------------------------
        // CASE 1: PROVISIONER WRITE MODE (Arm command received)
        // ----------------------------------------------------
        if (strcmp(status, "write_mode") == 0 || strcmp(action, "write_card") == 0) {
          int studentId = resDoc["student_id"] | 0;
          const char* sName = resDoc["student_name"] | "Student";
          const char* sRoll = resDoc["roll_no"] | "-";
          const char* sClass = resDoc["class_name"] | "Class";
          const char* payloadHex = resDoc["payload_hex"] | "";
          byte startBlock = resDoc["start_block"] | 4;

          // Write 48-byte cryptographic payload across Blocks 4, 5, and 6
          bool writeOk = writeCardSectors(startBlock, payloadHex);

          HTTPClient confirmHttp;
          confirmHttp.begin(client, String(SERVER_HOST) + "/api/v1/rfid/card/write-confirm");
          confirmHttp.addHeader("Content-Type", "application/json");

          StaticJsonDocument<256> confDoc;
          confDoc["device_id"] = DEVICE_ID;
          confDoc["card_uid"] = cardUid;
          confDoc["student_id"] = studentId;

          if (writeOk) {
            confDoc["status"] = "success";
            String confBody;
            serializeJson(confDoc, confBody);
            confirmHttp.POST(confBody);
            confirmHttp.end();

            Serial.println(F("[WRITE OK] Smart card 48-byte HMAC block written & confirmed to SIMS."));
            showScreenCardWritten(sName, sRoll, sClass);
          } else {
            confDoc["status"] = "failed";
            String confBody;
            serializeJson(confDoc, confBody);
            confirmHttp.POST(confBody);
            confirmHttp.end();

            Serial.println(F("[WRITE ERROR] Failed to write card sectors 4-6."));
            showScreenError("WRITE FAILED!", "Check Card / Retry");
          }
          delay(2500);
          showScreenIdle();
          mfrc522.PICC_HaltA();
          mfrc522.PCD_StopCrypto1();
          return;
        }

        // ----------------------------------------------------
        // CASE 2: CARD INSPECT MODE (Admin Inspector Tab Active)
        // ----------------------------------------------------
        if (strcmp(status, "inspect_mode") == 0 || strcmp(action, "inspect_card") == 0) {
          showScreenInspecting();

          byte rawBuffer[48];
          memset(rawBuffer, 0, sizeof(rawBuffer));
          bool readOk = readCardSectors(4, 3, rawBuffer);
          String hexPayload = "";
          if (readOk) {
            hexPayload = bytesToHex(rawBuffer, 48);
            Serial.println(F("[INSPECT] Physical EEPROM Payload (Blocks 4-6):"));
            Serial.println(hexPayload);
          } else {
            Serial.println(F("[INSPECT WARN] Unsealed/blank card or Key A mismatch."));
          }

          // Submit to SIMS /api/v1/rfid/card/inspect
          HTTPClient inspectHttp;
          inspectHttp.begin(client, String(SERVER_HOST) + "/api/v1/rfid/card/inspect");
          inspectHttp.addHeader("Content-Type", "application/json");
          inspectHttp.addHeader("Accept", "application/json");

          StaticJsonDocument<512> insDoc;
          insDoc["device_id"] = DEVICE_ID;
          insDoc["card_uid"] = cardUid;
          if (readOk) {
            insDoc["payload_hex"] = hexPayload;
          }

          String insBody;
          serializeJson(insDoc, insBody);
          int insCode = inspectHttp.POST(insBody);
          String insRes = inspectHttp.getString();
          inspectHttp.end();

          Serial.print(F("[INSPECT HTTP] Status Code: "));
          Serial.println(insCode);
          Serial.print(F("[INSPECT HTTP] Response: "));
          Serial.println(insRes);

          if (insCode == 200) {
            StaticJsonDocument<1024> auditDoc;
            deserializeJson(auditDoc, insRes);
            const char* sName = auditDoc["student"]["name"] | "Unregistered Card";
            bool hmacValid = auditDoc["hmac_verified"] | false;
            showScreenInspectSuccess(sName, hmacValid);
          } else {
            String errDetail = "HTTP " + String(insCode);
            showScreenError("INSPECT ERROR", errDetail.c_str());
          }

          delay(2500);
          showScreenIdle();
          mfrc522.PICC_HaltA();
          mfrc522.PCD_StopCrypto1();
          return;
        }

        if (strcmp(status, "success") == 0) {
          const char* sName = resDoc["student"]["name"] | "Student";
          const char* sRoll = resDoc["student"]["roll_no"] | "-";
          const char* sClass = resDoc["student"]["class_name"] | "Class";

          if (strcmp(action, "already_marked") == 0) {
            const char* checkTime = resDoc["check_in_at"] | "Earlier";
            showScreenAlreadyMarked(sName, checkTime);
          } else {
            const char* checkTime = resDoc["attendance_summary"]["check_in_time"] | "Today";
            showScreenAttendanceSuccess(sName, sRoll, sClass, checkTime);
          }
        } else if (strcmp(status, "unknown_card") == 0) {
          showScreenError("UNKNOWN CARD", cardUid.c_str());
        } else if (strcmp(status, "blocked_card") == 0) {
          showScreenError("CARD SUSPENDED!", "Report to Admin Office");
        } else {
          const char* msg = resDoc["message"] | "Error";
          showScreenError("ACCESS DENIED", msg);
        }
      } else {
        showScreenError("SERVER ERROR", "Invalid JSON format");
      }
    } else {
      File f = SPIFFS.open("/offline_scans.txt", "a");
      if (f) {
        f.println(cardUid + "," + String(millis()));
        f.close();
      }
      showScreenError("OFFLINE BUFFERED", cardUid.c_str());
    }
  } else {
    File f = SPIFFS.open("/offline_scans.txt", "a");
    if (f) {
      f.println(cardUid + "," + String(millis()));
      f.close();
    }
    showScreenError("OFFLINE BUFFERED", cardUid.c_str());
  }

  delay(2500);
  showScreenIdle();

  mfrc522.PICC_HaltA();
  mfrc522.PCD_StopCrypto1();
}