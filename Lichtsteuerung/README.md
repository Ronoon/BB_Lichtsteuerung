## Lichtsteuerung by BB
Die Basis für dieses Modul ist die originale Treppenhauslichtsteuerung von SYMCON. 
Diese entsprach in einigen Dingen nicht meinem UseCase, daher wurden entsprechende Erweiterungen hinzugefügt.
Da es nun sehr viel universeller ist wurde der Modulname auf BB_Lichtsteuerung geändert. Das Modulkürzel für PHP Scripte ist nun BBL_

## Änderungen gegenüber dem Original:
### Dauerlicht mit automatischem Rückfall auf Normalbetrieb. 
Die Dauerlichtfunktion ist als Taster implementiert. d.h. bei jeder Tasterbetätigung (Aktualisierung)  wird Dauerlicht An/Abgeschaltet. Es können beliebig viele Trigger (Taster) hinzugefügt werden. 
Durch die automatische Rückfallfunktion wird verhindert das vergessen wird das Dauerlicht auch wieder abzuschalten. Der Rückfall auf Normalbetrieb erfolgt nach einer vorwählbaren Timerzeit.
Dauerlicht ist auch als neue Statusvariable verfügbar. Dies bildet ab ob Dauerlicht Ein oder Abgeschaltet ist. Die Variable kann auch zum Schalten per Script oder zum Triggern weiterer Scripte oder Module verwendet werden. 

### Statusvariable für Licht Ein/Aus
Diese ist dafür gedacht um weitere Module oder Ablaufpläne oder auch weitere Scripte mit komplexeren Funktionen triggern zu können 

### Erweiterte Tag/Nachtfunktion 
Diese wurde dahingehend verändert das im Falle von 'Tag' und der Dimmwert für Tag auf '0' eingestellt ist kein Licht eingeschaltet wird. 
Im Falle von Nacht wird auf den vorgewählten Dimmwert gedimmt und Schaltervariablen werden entsprechend eingeschaltet.

### Starten eines Scriptes
Zusätzlich ODER alternativ zu den Augsabevaribalen kann auch ein Script definiert werden welches bei Licht AN/AUS gestartet wird.
Der aktuelle Status ist im Script über $_IPS['VALUE'] auswertbar. 
Die Verwendung eines externen Scriptes ist sinnvoll um, auch komplexere Kommandos wie Auf/Abdimmzeiten oder komplexe Lichtscenen bei Licht An/Aus realisieren zu können. Oder auch gegenseitiges verriegeln mehrerer Lichtsteuerungsinstanzen sollte damit realisierbar sein. 

### Debug Meldungen
Im Debug Fenster werden nun zu allen Aktionen und Statusänderungen entsprechende Log Messages ausgegeben. 

### Einschaltdauer
Die Einschaltdauer kann nun als Dezimalzahl angegeben werden. Dadurch ist eine feinere Auflösung möglich. zb 0.5 Minuten -> 30 sec Intervall.

### Konfigurationsformular 
Das Formular wurde etwas umgebaut und die optionen in mehrere Gruppierungen unterteilt.

### ab hier die Originaldoku
---------------------------------------------------------------------------------------------------

# Treppenhauslichtsteuerung
Nachdem ein Auslöser aktiviert wird, geht das Licht im Treppenhaus an. Wird der Auslöser wiederholt aktiviert bleibt das Licht an und der Timer wird zurückgesetzt. Erst wenn für eine vorgegebene Zeit keine weitere Auslösung stattfindet wird das Licht ausgeschaltet.


### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Auswahl von Ein- und Ausgabevariablen in einer Liste.
* Auswahl der Dauer bevor das Licht ausgeschaltet wird.
* Angabe der Helligkeit in Abhängigkeit einer Nacht-Modus Variable
* .Reversed Profile werden sowohl für Ein- als auch Ausgabevariablen unterstützt
* Möglichkeit die verbleibende Zeit bis zum Auschalten anzuzeigen.

### 2. Voraussetzungen

- IP-Symcon ab Version 5.0

### 3. Software-Installation

* Über den Module Store das Modul Treppenhauslichtsteuerung installieren.
* Alternativ über das Module Control folgende URL hinzufügen:
`https://github.com/symcon/Treppenhauslichtsteuerung`

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" kann das 'Treppenhauslicht'-Modul mithilfe des Schnellfilters gefunden werden.
    - Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name                            | Beschreibung
------------------------------- | ---------------------------------
Eingabesensoren                 | Liste der Eingabesensoren, bei deren Aktivierung das Licht aktiviert werden soll, z.B. Bewegungssensoren oder Taster - Das Licht wird aktiviert sobald eine Variable auf aktiv gesetzt wird. Als aktiv gelten hierbei Variablen mit einem Wert, der nicht false, 0, oder "" ist. Sollte die Variable ein .Reversed Profil haben gelten die genannten Werte als aktiv.
Ausgabevariablen                | Liste der Variablen, welche aktiv, also auf ihren Maximalwert geschaltet werden und das Licht darstellen. Sollte eine Variable ein .Reversed Profil haben wird diese auf den Minimalwert geschaltet. Variablen des Typs String werden nicht geschaltet. Die Variablen werden akiv geschaltet, wenn ein Sensor aus der Eingabesensor Liste ausgelöst wird.
Dauer                           | Nachdem die ausgewählte Dauer ohne weitere Auslösung eines Eingabesensors vergeht, wird das Licht deaktiviert.
Aktion erneut senden            | Wenn ein unzuverlässiges Funk-System verwendet wird, so ist es ggf. erforderlich bei jedem Impuls die Aktion zu senden. Im Normalfall sollte diese Option deaktiviert bleiben, da ständiges senden der Aktion bei Funk-Aktoren ggf. den Duty-Cycle aufbrauchen kann. 
Restlaufzeit anzeigen           | Wenn aktiv wird die verbleibende Zeit bis zum Ausschalten in einer Variable angezeigt.
Aktualisierungsintervall        | Das Intervall, in dem die "Restzeit" Variable aktualisiert wird.
Nacht-/Tag-Modus                | Ermöglicht es die gewählten Variablen basiernd auf der Tageszeit, oder der Umgebungshelligkeit auf unterschiedliche Werte zu schalten 

__Nacht-/Tag-Modus - Nacht-/Tag Varaible__
Name                     | Beschreibung
-------------------------| ---------------------------------
Tag/Nacht                | Eine Variable, die angibt ob Nacht oder Tag ist.
Invertiert               | Gibt an, ob der Wert der Nacht-Modus Variable invertiert werden soll. Dies ist notwendig, wenn die Ist-Tag Variable von der Location Instanz verwendet werden soll. Diese ist nämlich FALSE, wenn es Dunkel ist.
Helligkeit (Nacht-Modus) | Gibt die Helligkeit in Prozent an, die Nachts geschaltet werden soll.
Helligkeit (Tag-Modus)   | Gibt die Helligkeit in Prozent an, auf die am Tag geschaltet werden soll.

__Nacht-/Tag-Modus - Umgebungshelligkeitsvariable__
Name                         | Beschreibung
---------------------------- | ---------------------------------
Umgebungshelligkeit          | Die Variable, die als Umgebungshelligkeit genutzt wird.
Umgebungshelligkeitsschwelle | Der Grenzwert, bei dem zwischen den Werten für Tag und Nacht gewechselt wird.
Helligkeit (Nacht-Modus)     | Gibt die Helligkeit in Prozent an, die bei Unterschreitung der Helligkeit geschaltet werden soll.
Helligkeit (Tag-Modus)       | Gibt die Helligkeit in Prozent an, die bei Überschreitung der Helligkeit geschaltet werden soll.

### 5. Statusvariablen und Profile

##### Statusvariablen

Name                       | Typ     | Beschreibung
-------------------------- | ------- | ---------------------------
Treppenhaussteuerung aktiv | Boolean | Die Variable gibt an, ob die Treppenhaussteuerung aktiviert ist
Restzeit                   | String  | Wenn "Restlaufzeit anzeigen" aktiv ist wird hier die verbleibende Zeit bis zum Auschalten angezeigt

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt.

### 6. WebFront

Über das WebFront werden keine zusätzlichen Informationen angezeigt.

### 7. PHP-Befehlsreferenz

`boolean THL_Start(integer $InstanzID);`  
Aktiviert das Licht im Treppenhaus und startet den Timer, welcher das Licht wieder deaktiviert. Bei wiederholtem Aufruf wird der Timer zurückgesetzt.

Beispiel:  
`THL_Start(12345);`

`boolean THL_Stop(integer $InstanzID);`
Deaktiviert das Licht im Treppenhaus und den Timer.

Beispiel:
`THL_Stop(12345);`

`boolean THL_SetActive(integer $InstanzID, boolean $Wert);`
Aktiviert oder deaktiviert die Treppenhauslichtsteuerung. Wurde das Treppenhauslicht durch die Steuerung eingeschaltet und die Steuerung wird deaktiviert, so wird der aktuelle Steuervorgang noch zu Ende geführt. Allerdings wird der Timer bei erneutem Auslösen des Eingabesensors nicht zurückgesetzt. Das Treppenhauslicht wird also trotz deaktivierter Steuerung nach Ablauf des Timers ausgeschaltet.

Beispiel:
`THL_SetActive(12345, true);`
