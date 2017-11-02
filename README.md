BlueIris Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul ermöglicht die Steuerung von BlueIris auf einem Windows PC.

**Content**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Vorbereitung & Installation & Konfiguration](#3-vorbereitung--installation--konfiguration)
4. [Funktionen](#6-funktionen)

## 1. Funktionsumfang  
Die folgenden Funktionalitäten sind implementiert:
- Abfragen der Streams als Mediaobjekt
- Kameras Aktivieren/Deaktivieren
- alle Kameraparameter sind als Variable verfügbar
- Alarmierung bei Bewegung aktivierbar/deaktivierbar
- Abfragen des Alarmbildes
- PTZ Steuerung der Kameras (wenn Unterstützt)

## 2. Anforderungen
- IP-Symcon 4.x installation (Linux / Windows)
- Bereits installiertes und konfiguriertes BlueIris

## 3. Vorbereitung & Installation & Konfiguration

### Installation in IPS 4.x
Im "Module Control" (Kern Instanzen->Modules) die URL "https://github.com/daschaefer/SymconBlueIris.git" hinzufügen.  
Danach ist es möglich eine neue BlueIris Instanz innerhalb des Objektbaumes von IP-Symcon zu erstellen. Nach erfolgreicher Konfiguration des Moduls werden automatisch alle Kameras als neue BlueIrisCamera Instanzen angelegt.

### Konfiguration
**Host:**

*Die IP-Adresse/Hostname des BlueIris Servers*

**Port:**

*Der Port des BlueIris Servers (Standard: 81)

**Timeout:**

*Maximale Laufzeit der CURL Requests in Sekunden (Standard: 3)*

**Intervall:**

*Aktualisierungsintervall der Variablen und Zustände der Kameras in Sekunden (Standard: 3)*

**Username:**

*Benutzername für den Zugriff auf den BlueIris Server*

**Password:**

*Passwort für den Zugriff auf den BlueIris Server*

**Alarmauslöser:**

*Es gibt zwei Möglichkeiten einen Alarm auszulösen: isTriggered oder isAlerting, dies muss ausprobiert werden*

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
Interne Updateroutine

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
BLUEIRISCAMERA_Record(InstanceID: Integer, state: Variant)
```
Aktiviert / Deaktiviert die Aufnahmefunktion der Kamera

---
```php
BLUEIRISCAMERA_Reset(InstanceID: Integer)
```
Setzt die Kamera zurück

---