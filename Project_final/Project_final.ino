#include <Wire.h>
#include <Arduino.h>
#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>

#define USE_SERIAL Serial
ESP8266WiFiMulti WiFiMulti;
#define DEVICE (0x53)      //ADXL345 device address
#define TO_READ (6)        //num of bytes we are going to read each time (two bytes for each axis)

byte buff[TO_READ] ;        //6 bytes buffer for saving data read from the device
char str[512];              //string buffer to transform data before sending it to the serial port
int regAddress = 0x32;      //first axis-acceleration-data register on the ADXL345
int x, y, z;                //three axis acceleration data
int xBias, yBias, zBias;
const int BUTTON_PIN = 10;
const int LED_PIN = 9;
const String ssid = "AndroidAP";
const String pass = "svfw7168";
const String server_ip = "192.168.43.82";
bool deviceOn = false;
unsigned long previousMillis = 0;        // will store last time LED was updated
const long interval = 1000;

void setup() {

  pinMode(LED_PIN, OUTPUT);
  pinMode(BUTTON_PIN, INPUT);
  Wire.begin();         // join i2c bus (address optional for master)

  //Turning on the ADXL345
  writeTo(DEVICE, 0x2D, 0);
  writeTo(DEVICE, 0x2D, 16);
  writeTo(DEVICE, 0x2D, 8);
  writeTo(DEVICE, 0x31, 11);

  USE_SERIAL.begin(115200);
  // USE_SERIAL.setDebugOutput(true);

  USE_SERIAL.println();

  WiFiMulti.addAP(ssid, pass);
  xBias = 0;
  yBias = 0;
  zBias = 0;
  digitalWrite(LED_PIN, HIGH);
  for (int i = 0; i < 100; i++) {
    calibrateAccelerometer();
  }
  xBias /= 100;
  yBias /= 100;
  zBias /= 100;
  digitalWrite(LED_PIN, LOW);
  USE_SERIAL.println("DEVICE READY");
}

void loop() {
  if (digitalRead(BUTTON_PIN) == HIGH) {
    while (digitalRead(BUTTON_PIN) == HIGH) {

    }
    deviceOn = !deviceOn;
    if (deviceOn) {
      USE_SERIAL.println("START");
      httpOpenLink("http://" + server_ip + "/startSleepRound.php");
    } else {
      USE_SERIAL.println("END OF ROUND");
      USE_SERIAL.println("");
    }
  }
  unsigned long currentMillis = millis();

  if (currentMillis - previousMillis >= interval) {
    // save the last time you blinked the LED
    previousMillis = currentMillis;
    if (deviceOn) {
      readAndSendData();
      digitalWrite(LED_PIN, HIGH);
    } else {
      digitalWrite(LED_PIN, LOW);
    }
  }
}

void readAndSendData() {
  readFrom(DEVICE, regAddress, TO_READ, buff); //read the acceleration data from the ADXL345
  //each axis reading comes in 10 bit resolution, ie 2 bytes.  Least Significat Byte first!!
  //thus we are converting both bytes in to one int

  x = (((int)buff[1]) << 8) | buff[0];
  y = (((int)buff[3]) << 8) | buff[2];
  z = (((int)buff[5]) << 8) | buff[4];
  short xx = (short)x;
  short yy = (short)y;
  short zz = (short)z;

  String link1 = "http://" + server_ip + "/sqlAddData.php?ax=";
  String link2 = "&ay=";
  String link3 = "&az=";
  String ax = (String) (xx - xBias);
  String ay = (String) (yy - yBias);
  String az = (String) (zz - zBias);

    //we send the x y z values as a string to the serial port
  USE_SERIAL.print("The acceleration info of x, y, z are:");
  USE_SERIAL.print(ax + " " + ay + " " + az);
  USE_SERIAL.write(10);
  //Roll & Pitch calculate
  RP_calculate();
  USE_SERIAL.println("");

  httpOpenLink(link1 + ax + link2 + ay + link3 + az);
}

void writeTo(int device, byte address, byte val) {
  Wire.beginTransmission(device); //start transmission to device
  Wire.write(address);        // send register address
  Wire.write(val);        // send value to write
  Wire.endTransmission(); //end transmission
}

//reads num bytes starting from address register on device in to buff array
void readFrom(int device, byte address, int num, byte buff[]) {
  Wire.beginTransmission(device); //start transmission to device
  Wire.write(address);        //sends address to read from
  Wire.endTransmission(); //end transmission

  Wire.beginTransmission(device); //start transmission to device
  Wire.requestFrom(device, num);    // request 6 bytes from device

  int i = 0;
  while (Wire.available())   //device may send less than requested (abnormal)
  {
    buff[i] = Wire.read(); // receive a byte
    i++;
  }
  Wire.endTransmission(); //end transmission
}

//calculate the Roll&Pitch
void RP_calculate() {
  double x_Buff = float(x);
  double y_Buff = float(y);
  double z_Buff = float(z);
}

void calibrateAccelerometer(void) {

  //Take a number of readings and average them
  //to calculate any bias the accelerometer may have.
  readFrom(DEVICE, regAddress, TO_READ, buff);
  x = (((int)buff[1]) << 8) | buff[0];
  y = (((int)buff[3]) << 8) | buff[2];
  z = (((int)buff[5]) << 8) | buff[4];
  short xx = (short)x;
  short yy = (short)y;
  short zz = (short)z;

  xBias = xx + xBias;
  yBias = yy + yBias;
  zBias = zz + zBias;

  delay(10);
}

void httpOpenLink(String url) {
  if (WiFiMulti.run() == WL_CONNECTED) {

    HTTPClient http;

//    USE_SERIAL.print("[HTTP] begin " + url + "\n");
    http.begin(url); //HTTP

//    USE_SERIAL.print("[HTTP] GET...\n");
    // start connection and send HTTP header
    int httpCode = http.GET();

    // httpCode will be negative on error
    if (httpCode > 0) {
      // HTTP header has been send and Server response header has been handled
//      USE_SERIAL.printf("[HTTP] GET... code: %d\n", httpCode);

      // file found at server
      if (httpCode == HTTP_CODE_OK) {
        String payload = http.getString();
        int index_start = payload.indexOf("#");
        int index_end = payload.lastIndexOf("#");
        
        USE_SERIAL.println(payload.substring(index_start+1, index_end));
      }
    } else {
      USE_SERIAL.printf("[HTTP] GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
    }

    http.end();
  }
}

