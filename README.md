BlueIris v2 Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul ermöglicht die Verwendung von Netzwerkkameras über BlueIris

**Content**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Vorbereitung & Installation & Konfiguration](#3-vorbereitung--installation--konfiguration)
4. [Funktionen](#6-funktionen)

## 1. Funktionsumfang  
Die folgenden Funktionalitäten sind implementiert:
- Abfragen der Streams als Mediaobjekt
- Kameras aktivieren/deaktivieren/pausieren
- Motion Detection aktivieren/deaktiviere
- Kameraparameter wählbar: minimal/vollständig (minimal ist Standard da deutlich weniger Variablen verwendet werden)
- Abfragen des Alarmbildes als ID

## 2. Anforderungen
- IP-Symcon 5.x installation (Linux / Windows)
- Bereits installiertes und konfiguriertes BlueIris

## 3. Vorbereitung & Installation & Konfiguration

### Installation in IPS 5.x
Im "Module Control" (Kern Instanzen->Modules) die URL "https://github.com/daschaefer/SymconBlueIris.git" mit dem Repository v2 hinzufügen.  
Danach ist es möglich eine neue BlueIris Instanz innerhalb des Objektbaumes von IP-Symcon zu erstellen. Nach erfolgreicher Konfiguration des Moduls werden automatisch alle Kameras als neue BlueIrisCamera Instanzen angelegt.

### Konfiguration
**Host:**

*Die IP-Adresse/Hostname des BlueIris Servers*

**Port:**

*Der Port des BlueIris Servers (Standard: 81)*

**Benutzername:**

*Benutzername für den Zugriff auf den BlueIris Server*

**Passwort:**

*Passwort für den Zugriff auf den BlueIris Server*

**Kameravariablen:**

*Minimal legt nur die wichtigsten Variablen an, Vollständig legt alle Variablen an die über die API übermittelt werden*

**Max. Breite pro Kamera im Raster:**

*Legt die maximale Breite in Pixeln pro Kamerabild im Raster fest. Standard ist 600px. Die Einstellung ermöglicht es je nach Auflösung des Endgerätes eine schöne Darstellung des Rasters herzustellen. Das Raster funktioniert nur bei deaktiviertem Image Grabber!*

**Image Grabber verwenden:**

*Kamerabild über Image Grabber holen, dies ermöglicht die Verwendung von Symcon Connect. Setzt die Rasterdarstellung außer Kraft.*

**Aktualisierungsintervall:**

*Aktualisierungsintervall für den Image Grabber in Sekunden (default 10s)*

**Webhook Benutzername:**

*Benutzername für den Zugriff auf den Webhook des Moduls*

**Webhook Passwort:**

*Passwort für den Zugriff auf den Webhook des Moduls*

### Konfiguration der Übermittlung eines Triggers an Symcon

1. BlueIris Adminkonsole öffnen

2. Die Eigenschaften der Kamera öffnen welche bei Bewegung einen Trigger senden soll.

3. Dort dann den Reiter 'Alerts' öffnen. Den merkierten Punkt anhaken und auf den Knopf 'Configure...' klicken:

![Kameraeigenschaften - Alerts](imgs/1.png?raw=true "Kameraeigenschaften - Alerts")

4. Dort als Typ 'http://' auswählen und folgenden String eintragen (Benutzername / Passwort der Modulkonfiguration innerhalb Symcon entnehmen):

![Aktionsgruppe](imgs/2.png?raw=true "Aktionsgruppe")
  - Benutzername:Passwort@Symcon-IP-Adresse:3777/hook/blueiris?cam=&CAM&action=trigger

## 4. Funktionen

```php
BLUEIRIS_DisableAlarm(InstanceID: Integer)
```
Deaktiviert die Alarmierung

---
```php
BLUEIRIS_EnableAlarm(InstanceID: Integer)
```
Aktiviert die Alarmierung

---
```php
BLUEIRIS_GetAlertList(InstanceID: Integer)
```
Gibt alle Informationen zur Alarmierung zurück

---
```php
BLUEIRIS_GetCamList(InstanceID: Integer)
```
Gibt alle installierten Kameras zurück

---
```php
BLUEIRIS_GetClipList(InstanceID: Integer)
```
Gibt alle Clips zurück

---
```php
BLUEIRIS_Query(InstanceID: Integer, param: Variant)
```
Anfragen an den BlueIris Server senden

---
```php
BLUEIRIS_ResetAlarm(InstanceID: Integer)
```
Setzt den Alarm zurück

---
```php
BLUEIRIS_Update(InstanceID: Integer)
```
Interne Updateroutine (sollte nicht verwendet werden)

---
```php
BLUEIRISCAMERA_Disable(InstanceID: Integer)
```
Deaktiviert die Kamera

---
```php
BLUEIRISCAMERA_Enable(InstanceID: Integer)
```
Aktiviert die Kamera

---
```php
BLUEIRISCAMERA_MotionDetection(InstanceID: Integer, state: Variant)
```
Aktiviert / Deaktiviert die Bewegungserkennung der Kamera

---
```php
BLUEIRISCAMERA_Pause(InstanceID: Integer, pause: Variant)
```
Pausiert die Kamera

---
```php
BLUEIRISCAMERA_PTZ(InstanceID: Integer, Camera: String, PTZ_CMD: int)
```
PTZ Steuerung der Kamera:

PTZ_CMD:
0: Pan left

1: Pan right

2: Tilt up

3: Tilt down

4: Center or home (if supported by camera)

5: Zoom in

6: Zoom out

8..10: Power mode, 50, 60, or outdoor

11..26: Brightness 0-15

27..33: Contrast 0-6

34..35: IR on, off

101..120: Go to preset position 1..20

---
```php
BLUEIRISCAMERA_Reset(InstanceID: Integer)
```
Setzt die Kamera zurück

---