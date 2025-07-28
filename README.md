# ClimaSite
Building a temperature network to view the status of the house at a glance.


## The controller:

### Seeed ESP32-C6

<img width="426" height="426" alt="image" src="https://github.com/user-attachments/assets/8984269c-3092-4731-a57a-66246f9eff08" />


![image](https://github.com/user-attachments/assets/032e0e83-2637-41fe-9aa3-2b1b53b858fe)

![image](https://github.com/user-attachments/assets/516f22a8-61ab-40cb-93ee-9342d2b8df12)


## The Probe:

### DS18B20 Waterproof Temperature sensor

![image](https://github.com/user-attachments/assets/e8cadad7-a3e1-4a98-bbe0-c2164b57896c)

Why a *waterproof* sensor?  
I had a few lying around for ages now, and don't really want to buy new ones.  
However, If you're doing this project - I'd recommend something like this:  
[DS18B20 on AliExpress](https://www.aliexpress.com/item/1005008644135794.html)  
<img width="542" height="925" alt="image" src="https://github.com/user-attachments/assets/07266416-cbc7-4355-80ad-716f488fa45d" />



## The Power

![image](https://github.com/user-attachments/assets/b7e58b6b-7ccb-4509-b86a-6579ff8ebd05)


## Physical Connections

| DS18B20 Wire | ESP32-C6 Connection  | Power Supply Connection | 4.7kΩ Resistor |
| ------------ | -------------------- | ----------------------- | -------------- |
| —            | **5V pin**           | **+5V input**           | —              |
| **Red**      | **3V3 pin**          | —                       | One end here   |
| **Black**    | **GND pin**          | **GND**                 | —              |
| **Yellow**   | **D10 / GPIO10 pin** | —                       | Other end here |


## The Plan (Construction)

Remove the USB inards of the usb power supply and provide +5V and GND to the controller. Hopefully there will be enough space in the housing for the ESP32-c6. The temperature probe can just hang out for the time being but I'll need to design a nice little vented enclosure for the thing that makes it look more intentional. Vented, so that air can pass over it like in a stevenson's box and not be impacted by the heat being generated from the USB charger

## The PLan (Final product)

When the USB Charger is plugged into a wall socket, the micro-processor will start up, connect to the wifi AP and "call home" to a hard-coded IP address.   
In that call will be the sensor's :

- IP address
- The DS18B20's UUID
- MAC Address

The UUID is a standard format that identifies each DS28B20 sensor and breaks down like this:  

[8-bit Family Code] + [48-bit Serial Number] + [8-bit CRC]

The first 2 characters of every DS18B20 sensor will be "28". This will be used to verify that the sensor is reporting correctly, I will also use that string to identify the rest of the UUID.

This waill allow for me to identify each device and then later assign an IP reservation to place it in the correct VLAN and have the appropriate IP.

The MariaDB Table will look something like this:

| ID | UUID                    | MAC Address        | IP Address   | First Seen       | 
| -- | ----------------------- | ------------------ | ------------ | ---------------- | 
| 1  | 28-FF-4C-60-91-16-03-55 | AA:BB:CC:DD:EE:FF  | 10.10.40.152 | 2025-07-02 14:33 | 
| 2  | 28-FF-8A-2D-78-16-04-91 | 11:22:33:44:55:66  | 10.10.40.158 | 2025-07-02 14:35 | 

Once the server has identified a new sensor, It will add a new row to a MariaDB database and start connecting to that sensor once per minute to get an updated temperature.

There'll be a second table with the list of rooms.

| ID | Floor | Room Name   |
| -- | ----- | ----------- |
| 1  | 2     | Living Room |
| 2  | 2     | Master Bed  |
| 3  | 2     | Kitchen     |
| 4  | 1     | Front Door  |
| 5  | 1     | Back Door   |

Then a 3rd table in case the sensors need to be moved at any point:

| Sensor ID | Room ID | Start Time       | End Time (optional) |
| --------- | ------- | ---------------- | ------------------- |
| 1         | 3       | 2025-07-01 09:00 | 2025-07-02 14:30    |
| 1         | 1       | 2025-07-02 14:33 | NULL                |

This will allow me to access historical data.

There will then naturally be a 4th table wilt the actaul readings from the sensors

| ID | Sensor ID | Timestamp  | Raw Value | Celsius |
| -- | --------- | ---------- | --------- | ------- |
| 1  | 1         | 1752091905 | 0x01A8    | 26.00   |
| 2  | 2         | 1752091905 | 0x01AC    | 26.25   |
| 3  | 3         | 1752091905 | 0x01B0    | 26.5    |
| 4  | 1         | 1752091965 | 0x01A8    | 26.00   |
| 5  | 2         | 1752091965 | 0x01AC    | 26.25   |
| 6  | 3         | 1752091965 | 0x01B0    | 26.5    |


However, the plan doesn't stop there. The intention is to the disaply a floorplan of the house with each room displaying it's temperature and maybe enve move into a colour gradient being displayed in the rooms.

## Progress

### July 26, 2025

<img width="424" height="350" alt="image" src="https://github.com/user-attachments/assets/85f17a97-c181-4aed-9311-30f4dcf0ff20" />

I'm getting readings. Thisis currently plugged into the wall in my living romm.

I've just finished construction opn my 2nd sensor.  
Here are some pictures of the contruction process:  


And here's the dashboard:  
<img width="1300" height="366" alt="image" src="https://github.com/user-attachments/assets/cf54c5f6-0eeb-496c-86dc-572b6d20c7fd" />


Here's the current file structure:  

```
/var/www/html/  
|-- api  
|      └-- sensors.php  
|-- display.php  
|-- fonts  
|      |-- roboto.woff2  
|-- includes  
|      └-- db.php  
|-- index.html  
|-- scripts  
|      |-- display.js  
|-- styles  
|      |-- dark-theme.css  
└-- upload.php  
```

## To DO:

### ESP32-C6

- [x] Code for the probe to start up, connect to the wifi and update the temperature - Also API server

### Seerverside: 

- [x] Databases
- [ ] Web site to allow easy editing of room names and locations
- [ ] Script to access API on all the sensors
- [x] Web site to display the termperatures in real-time
