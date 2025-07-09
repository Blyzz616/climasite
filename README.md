# ClimaSite
Building a temperature network to view the status of the house at a glance.


## The controller:

### Seeed ESP32-C6

![image](https://github.com/user-attachments/assets/e8d951df-8d4a-44dd-a488-af3d2030030b)

![image](https://github.com/user-attachments/assets/032e0e83-2637-41fe-9aa3-2b1b53b858fe)

![image](https://github.com/user-attachments/assets/516f22a8-61ab-40cb-93ee-9342d2b8df12)


## The Probe:

### DS18B20 Waterproof Temperature sensor

![image](https://github.com/user-attachments/assets/e8cadad7-a3e1-4a98-bbe0-c2164b57896c)


## Physical Connections

| DS18B20 Wire | ESP32-C6 Connection  | Power Supply Connection | 4.7kΩ Resistor |
| ------------ | -------------------- | ----------------------- | -------------- |
| —            | **5V pin**           | **+5V input**           | —              |
| **Red**      | **3V3 pin**          | —                       | One end here   |
| **Black**    | **GND pin**          | **GND**                 | —              |
| **Yellow**   | **D10 / GPIO10 pin** | —                       | Other end here |


## The Plan

When the USB Charger is plugged into a wall socket, the micro-processor will start up, connect to the wifi AP and "call home" to a hard-coded IP address. In that call will be the sensor's IP address and the sensor's unique hexadecimal identifier. Once the server has identified a new sensor, It will add a new row to a MariaDB database and start connecting to that sensor once per minute to get an updated temperature.

The MariaDB server will then need to be edited to manually add some more data about the row - including things like the room name and floor etc.

This will allow me to access historical data.

However, the plan doesn't stop there. The intention is to the disaply a floorplan of the house with each room displaying it's temperature and maybe enve move into a colour gradient being displayed in the rooms.

## To DO:

### ESP32-C6

- [ ] Code for the probe to start up, connect to the wifi and update the temperature - Also API server

### Seerverside: 

- [ ] Web site to allow easy editing of room names and locations
- [ ] Script to access API on all the sensors
- [ ] Web site to display the termperatures in real-time
