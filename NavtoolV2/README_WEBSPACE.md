# NAVTOOL 2.0 — PHP/MySQL Webspace Edition

Diese Version erweitert dein bestehendes NAVTOOL um:
- PHP/MySQL-Backend
- Admin Panel unter `/admin/`
- Website online/offline
- Module an/aus
- anonyme Besucherstatistik
- Geräte-/Browser-Auswertung
- Live-Besucher (nur technische, anonyme Sessiondaten)
- Werbebanner direkt unter dem HUB-Suchfeld
- optionale Pop-up-Werbung
- Laufzeiten und automatische Deaktivierung von Kampagnen
- CSRF-geschützte Admin-Formulare
- Passwort-Hashing

## 1. MySQL
In Plesk eine MySQL-Datenbank anlegen.
Dann `database/schema.sql` in phpMyAdmin importieren.

## 2. Zugangsdaten
`config/config.php` öffnen und DB_HOST, DB_NAME, DB_USER, DB_PASS und ANALYTICS_SALT setzen.
ANALYTICS_SALT sollte ein langer zufälliger geheimer Wert sein.

## 3. Hochladen
Den kompletten Inhalt dieses Ordners in den Domain-Root von navtool.de hochladen.
`index.php` ist die neue Startseite.

## 4. Admin einrichten
Einmal `https://navtool.de/admin/setup.php` öffnen.
Admin-Benutzer und ein Passwort mit mindestens 10 Zeichen setzen.
Danach `admin/setup.php` vom Webspace löschen.

Login:
`https://navtool.de/admin/login.php`

## 5. HTTPS
Für die Website HTTPS/Let's Encrypt aktivieren.

## 6. Wichtig
`config/config.php` niemals öffentlich ausliefern. Der Ordner ist zusätzlich per `.htaccess` geschützt.

## Datenschutz
Die Statistik ist absichtlich datensparsam:
- keine Klartext-IP wird in der Datenbank gespeichert
- IP wird mit einem geheimen Salt und Tageswert gehasht
- Referrer wird auf Origin reduziert
- Live-Besucher sind anonyme technische Sitzungen
- Statistik kann im Admin Panel deaktiviert werden

Für den produktiven öffentlichen Betrieb sollten Impressum, Datenschutzerklärung und die konkrete Rechtsgrundlage/Consent-Konfiguration noch geprüft werden.
