/**
 * BasicHTTPClient.ino
 *
 *  Created on: 24.05.2015
 *
 */
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

void setup() {

    Wire.begin();         // join i2c bus (address optional for master)
    
  //Turning on the ADXL345
    writeTo(DEVICE, 0x2D, 0);      
    writeTo(DEVICE, 0x2D, 16);
    writeTo(DEVICE, 0x2D, 8);
    writeTo(DEVICE, 0x31, 11);

    USE_SERIAL.begin(115200);
   // USE_SERIAL.setDebugOutput(true);

    USE_SERIAL.println();
    USE_SERIAL.println();
    USE_SERIAL.println();

    for(uint8_t t = 4; t > 0; t--) {
        USE_SERIAL.printf("[SETUP] WAIT %d...\n", t);
        USE_SERIAL.flush();
        delay(1000);
    }

    WiFiMulti.addAP("McDonan", "21091996");
    xBias = 0;
    yBias=0;
    zBias=0;
    for(int i = 0; i<30; i++){
    calibrateAccelerometer();
    }
    xBias /= 30;
    yBias /= 30;
    zBias /= 30;

    if((WiFiMulti.run() == WL_CONNECTED)) {

        HTTPClient http;

        USE_SERIAL.print("[HTTP] begin...\n");
        // configure traged server and url
        //http.begin("https://192.168.1.12/test.html", "7a 9c f4 db 40 d3 62 5a 6e 21 bc 5c cc 66 c8 3e a1 45 59 38"); //HTTPS
        http.begin("http://172.20.10.3/startSleepRound.php"); //HTTP

        USE_SERIAL.print("[HTTP] GET startSleepRound...\n");
        // start connection and send HTTP header
        int httpCode = http.GET();

        // httpCode will be negative on error
        if(httpCode > 0) {
            // HTTP header has been send and Server response header has been handled
            USE_SERIAL.printf("[HTTP] GET... code: %d\n", httpCode);

            // file found at server
            if(httpCode == HTTP_CODE_OK) {
                String payload = http.getString();
                USE_SERIAL.println(payload);
            }
        } else {
            USE_SERIAL.printf("[HTTP] GET... startSleepRound failed, error: %s\n", http.errorToString(httpCode).c_str());
        }

        http.end();
    }


}

void loop() {
  readFrom(DEVICE, regAddress, TO_READ, buff); //read the acceleration data from the ADXL345
                                              //each axis reading comes in 10 bit resolution, ie 2 bytes.  Least Significat Byte first!!
                                              //thus we are converting both bytes in to one int

  

  for(int i = 0; i < 6; i++){
    USE_SERIAL.print(buff[i],BIN);
    USE_SERIAL.print(" ");
  }
  USE_SERIAL.println("");

  x = (((int)buff[1]) << 8) | buff[0];   
  y = (((int)buff[3])<< 8) | buff[2];
  z = (((int)buff[5]) << 8) | buff[4];
  short xx =(short)x;
  short yy = (short)y;
  short zz = (short)z;

  //we send the x y z values as a string to the serial port
  USE_SERIAL.print("The acceleration info of x, y, z are:");
  sprintf(str, "%d %d %d", x, y, z);  
  USE_SERIAL.print(str);
  USE_SERIAL.write(10);
  //Roll & Pitch calculate
  RP_calculate();
  USE_SERIAL.println("");
  USE_SERIAL.print(xx);
  USE_SERIAL.print(" ");
  USE_SERIAL.print(yy);
  USE_SERIAL.print(" ");
  USE_SERIAL.print(zz);
  //It appears that delay is needed in order not to clog the port

  USE_SERIAL.print(" ");
  USE_SERIAL.print(xx,BIN);
  USE_SERIAL.print(" ");
  USE_SERIAL.print(yy,BIN);
  USE_SERIAL.print(" ");
  USE_SERIAL.print(zz,BIN);
  USE_SERIAL.println("");

  
  
  USE_SERIAL.print("two's complements are : ");
  sprintf(str, "%d %d %d", xx, yy, zz);  
  USE_SERIAL.print(str);
  USE_SERIAL.write(10);
  //Roll & Pitch calculate
  RP_calculate();
  USE_SERIAL.println("");
  delay(1000);
    // wait for WiFi connection
    String link1 = "http://172.20.10.3/sqlAddData.php?ax=";
    String link2 = "&ay=";
    String link3 = "&az=";
    String ax = (String) (xx-xBias);
    String ay = (String) (yy-yBias);
    String az = (String) (zz-zBias);
    if((WiFiMulti.run() == WL_CONNECTED)) {

        HTTPClient http;

        USE_SERIAL.print("[HTTP] begin...\n");
        // configure traged server and url
        //http.begin("https://192.168.1.12/test.html", "7a 9c f4 db 40 d3 62 5a 6e 21 bc 5c cc 66 c8 3e a1 45 59 38"); //HTTPS
        http.begin(link1+ax+link2+ay+link3+az); //HTTP

        USE_SERIAL.print("[HTTP] GET...\n");
        // start connection and send HTTP header
        int httpCode = http.GET();

        // httpCode will be negative on error
        if(httpCode > 0) {
            // HTTP header has been send and Server response header has been handled
            USE_SERIAL.printf("[HTTP] GET... code: %d\n", httpCode);

            // file found at server
            if(httpCode == HTTP_CODE_OK) {
                String payload = http.getString();
                USE_SERIAL.println(payload);
            }
        } else {
            USE_SERIAL.printf("[HTTP] GET... failed, error: %s\n", http.errorToString(httpCode).c_str());
        }

        http.end();
    }

    delay(1000);
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
  while(Wire.available())    //device may send less than requested (abnormal)
  { 
    buff[i] = Wire.read(); // receive a byte
    i++;
  }
  Wire.endTransmission(); //end transmission
}

//calculate the Roll&Pitch
void RP_calculate(){
  double x_Buff = float(x);
  double y_Buff = float(y);
  double z_Buff = float(z);
}
void calibrateAccelerometer(void) {
    
    //Take a number of readings and average them
    //to calculate any bias the accelerometer may have.
   readFrom(DEVICE, regAddress, TO_READ, buff);
    x = (((int)buff[1]) << 8) | buff[0];   
    y = (((int)buff[3])<< 8) | buff[2];
    z = (((int)buff[5]) << 8) | buff[4];
    short xx =(short)x;
    short yy = (short)y;
    short zz = (short)z;

    xBias = xx+xBias;
    yBias = yy+yBias;
    zBias = zz+zBias;
}

