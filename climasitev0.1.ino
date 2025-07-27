#include <WiFi.h>                 // Wi-Fi connectivity
#include <HTTPClient.h>           // HTTP client for sending POST requests
#include <OneWire.h>              // 1-Wire protocol support
#include <DallasTemperature.h>    // Library for DS18B20 temperature sensor

// GPIO pin connected to the data line of the DS18B20 sensor
// This will be D8 on the XIAO Esp32-C6
#define ONE_WIRE_BUS 19

// Wi-Fi credentials
#define WIFI_SSID ""                   // ⚠️ Replace with your Wi-Fi SSID
#define WIFI_PASSWORD ""    // ⚠️ Replace with your Wi-Fi password

// Server endpoint for uploading temperature data
#define SERVER_URL "http://192.168.0.200/upload.php"

// Set up 1-Wire communication on specified pin
OneWire oneWire(ONE_WIRE_BUS);

// Use DallasTemperature to communicate with DS18B20 sensor
DallasTemperature sensors(&oneWire);

// Address of the temperature sensor (will be detected at runtime)
DeviceAddress sensorAddress;

// Connects the device to Wi-Fi
void connectToWiFi() {
  Serial.print("Connecting to Wi-Fi");
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  int retries = 0;
  while (WiFi.status() != WL_CONNECTED && retries < 20) {
    delay(500);
    Serial.print(".");
    retries++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWi-Fi connected!");
    Serial.print("IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nFailed to connect to Wi-Fi");
  }
}

// Converts a device address (8-byte) to a formatted string (UUID-like)
String formatAddress(DeviceAddress addr) {
  char uid[24];
  snprintf(uid, sizeof(uid), "%02X-%02X-%02X-%02X-%02X-%02X-%02X-%02X",
           addr[0], addr[1], addr[2], addr[3],
           addr[4], addr[5], addr[6], addr[7]);
  return String(uid);
}

void setup() {
  Serial.begin(115200);           // Start serial communication
  sensors.begin();                // Initialize the DS18B20 sensor
  connectToWiFi();                // Connect to Wi-Fi

  Serial.println("Scanning for DS18B20 sensors...");
  // Attempt to retrieve the address of the first sensor
  if (sensors.getAddress(sensorAddress, 0)) {
    Serial.print("Found sensor 0: ");
    Serial.println(formatAddress(sensorAddress));
  } else {
    Serial.println("No DS18B20 sensor found!");
  }
}

void loop() {
  // Check that a sensor is available
  if (!sensors.getAddress(sensorAddress, 0)) {
    Serial.println("No DS18B20 sensor available. Skipping measurement.");
    delay(60000); // Wait one minute before retry
    return;
  }

  // Request temperature measurement
  sensors.requestTemperatures();
  float tempC = sensors.getTempC(sensorAddress);

  // Check if the sensor is disconnected
  if (tempC == DEVICE_DISCONNECTED_C) {
    Serial.println("Error: Sensor disconnected.");
    delay(60000);
    return;
  }

  // Only send data if connected to Wi-Fi
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");

    // Format sensor address and prepare JSON payload
    String uid = formatAddress(sensorAddress);
    String postData = "{\"uuid\":\"" + uid + "\",\"temperature\":" + String(tempC, 2) + "}";

    // Send POST request
    int httpResponseCode = http.POST(postData);

    Serial.print("POST response: ");
    Serial.println(httpResponseCode);

    // Output server response if successful
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Server response: " + response);
    } else {
      Serial.println("POST failed");
    }

    http.end(); // Close the connection
  } else {
    Serial.println("Not connected to Wi-Fi.");
  }

  delay(60000);  // Wait 60 seconds before the next reading
}
