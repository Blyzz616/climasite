// ESP32-C6 DS18B20 temperature reporting to central server
// Uses WiFi and HTTP

#include <WiFi.h>
#include <OneWire.h>
#include <DallasTemperature.h>
#include <HTTPClient.h>

// WiFi credentials
// Insert SSID And PSK
const char* ssid = "";
const char* password = "";

// Server config
// Insert Server IP
const char* server_ip = ""; 
const int server_port = 80;
const char* announce_endpoint = "/announce";
const char* temp_endpoint = "/temperature";

// DS18B20 setup
#define ONE_WIRE_BUS 18 // GPIO pin
OneWire oneWire(ONE_WIRE_BUS);
DallasTemperature sensors(&oneWire);

DeviceAddress sensorAddress;

WiFiServer server(80);

void connectToWiFi() {
  WiFi.begin(ssid, password);
  //Serial.print("Connecting to WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    //Serial.print(".");
  }
  //Serial.println("\nConnected!");
}

String getSensorUID() {
  sensors.begin();
  if (!sensors.getAddress(sensorAddress, 0)) {
    //Serial.println("No DS18B20 found");
    return "UNKNOWN";
  }
  char addrStr[24];
  snprintf(addrStr, sizeof(addrStr), "%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X",
           sensorAddress[0], sensorAddress[1], sensorAddress[2], sensorAddress[3],
           sensorAddress[4], sensorAddress[5], sensorAddress[6], sensorAddress[7]);
  return String(addrStr);
}

void announceToServer(const String& uid) {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String url = String("http://") + server_ip + ":" + server_port + announce_endpoint;
    http.begin(url);
    http.addHeader("Content-Type", "application/json");

    String payload = String("{\"ip\":\"") + WiFi.localIP().toString() + "\",\"uid\":\"" + uid + "\"}";
    int httpResponseCode = http.POST(payload);
    Serial.printf("Announce response: %d\n", httpResponseCode);
    http.end();
  }
}

float readTemperature() {
  sensors.requestTemperatures();
  return sensors.getTempCByIndex(0);
}

void handleClientRequest(WiFiClient client) {
  String request = client.readStringUntil('\r');
  client.readStringUntil('\n'); // consume newline

  if (request.startsWith("GET /temperature")) {
    float temp = readTemperature();
    client.println("HTTP/1.1 200 OK");
    client.println("Content-Type: application/json");
    client.println("Connection: close");
    client.println();
    client.printf("{\"temperature\": %.2f}\n", temp);
  } else {
    client.println("HTTP/1.1 404 Not Found");
    client.println("Connection: close");
    client.println();
  }
  delay(1);
  client.stop();
}

void setup() {
  Serial.begin(115200);
  connectToWiFi();
  String uid = getSensorUID();
  announceToServer(uid);
  server.begin();
  //Serial.println("Ready to serve temperature on /temperature");
}

void loop() {
  WiFiClient client = server.available();
  if (client) {
    handleClientRequest(client);
  }
}
