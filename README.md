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

*Der Port des BlueIris Servers (Standard: 81)

**Timeout:**

*Maximale Laufzeit der CURL Requests in Sekunden (Standard: 3)*

**Intervall:**

*Aktualisierungsintervall der Variablen und Zustände der Kameras in Sekunden (Standard: 3). Der Wert 0 deaktiviert die Kommunikation zu BlueIris*

**Username:**

*Benutzername für den Zugriff auf den BlueIris Server*

**Password:**

*Passwort für den Zugriff auf den BlueIris Server*


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
BLUEIRISCAMERA_PTZ(InstanceID: Integer, value: Variant)
```
PTZ Steuerung der Kamera

---
```php
BLUEIRISCAMERA_Reset(InstanceID: Integer)
```
Setzt die Kamera zurück

---