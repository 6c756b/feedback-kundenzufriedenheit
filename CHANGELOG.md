# Changelog

---

## [Unreleased]

- Referenzmanagement überarbeitung
- Logos und Hero-Bild
- Farbschema anpassbar

---

## [0.1.3a] - 2026-07-11

### Neu

- **CRM-Anbindung:** Befragungen können aus einem externen CRM-System heraus angelegt und abgerufen werden
- **API-Schlüsselverwaltung** unter Einstellungen: Schlüssel mit Namen, Berechtigungen (lesen/schreiben) und optionalem Ablaufdatum; einmalige Anzeige des Schlüssels nach Erstellung
- **Mobile Navigation:** Backend auf kleinen Bildschirmen nutzbar — aufklappbare Seitenleiste, zusammenklappbares Kontextpanel
- Referenzstatus (erteilt / abgelehnt) wird bei der CRM-Synchronisation zurückgemeldet

### Behoben

- Frageanzahl in der Bereichs-Navigation wurde auf der Bearbeitungsseite nicht angezeigt

---

## [0.1.2a] - 2026-06-13

### Geändert

- Backend-Navigation: obere Navigationsleiste durch linke Seitenleiste ersetzt
- Benachrichtigungen erscheinen jetzt als Toast-Meldungen statt als Seitenblock
- Benutzer- und Profilformular neu gegliedert

---

## [0.1.1a] - 2026-06-13

### Neu

- Pro Benutzer einstellbare Anmeldemethode: LDAP, lokales Passwort oder beides
- Benutzername in der Navigation als Link zur eigenen Profilseite

### Geändert

- LDAP-Benutzer ohne lokales Passwort können sich nicht mehr über das Passwort-Formular anmelden

---

## [0.1a] - 2026-06-10

Erste Alpha-Version. Kernfunktionen sind einsatzbereit.

### Neu

- Kundenbefragung per 8-stelligem Zugangscode (kein Kundenkonto erforderlich)
- Fragen: Slider (Note 1–6), Freitext oder beides kombiniert
- Befragungsstatus: Offen → Gestartet → Abgeschlossen → Archiviert
- Referenzabfrage am Ende der Befragung (optional je Befragung)
- CSV-Massenimport: mehrere Befragungen auf einmal anlegen
- Dashboard mit Übersicht offener Befragungen, Bewertungen und Referenzstatus
- Auswertungsansicht: als gelesen/ungelesen markieren, archivieren, PDF exportieren
- E-Mail-Versand direkt aus dem Backend oder als Outlook-Vorlage zum Kopieren
- Benutzerverwaltung mit fünf Rollen (Leser, Mitarbeiter, Admin, Superadmin)
- Fragenverwaltung mit Drag-and-Drop-Sortierung pro Bereich
- Vollständiges Aktivitätsprotokoll (nur Superadmin)
