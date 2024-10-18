# Dokumentation

## Kursbeschreibung des Projekts
(ca. 500 Zeichen)
Dieses Projekt untersucht den Zusammenhang zwischen finanziellem Potenzial und sportlichem Erfolg im Fussball. Im Fokus steht die Analyse aller Champions-League-Teams der aktuellen Saison, wobei die Marktwerte der Mannschaften mit ihrer Performance auf nationaler und internationaler Ebene verglichen werden. Ein besonderer Schwerpunkt liegt darauf, wie effizient ein Team im Verhältnis zu seinem Marktwert wirklich performt. Die zentrale Frage lautet: Wie stark beeinflussen finanzielle Ressourcen den sportlichen Erfolg und wie effizient setzen Teams ihre finanziellen Mittel ein?

Das Ziel des Projekts ist es, ein tieferes Verständnis für die Rolle des Geldes im modernen Fußball zu gewinnen und zu analysieren, wie effektiv Mannschaften im Vergleich zu ihrem Marktwert agieren. Diese Analyse richtet sich an Fußballinteressierte, Analysten und Experten, die die ökonomischen und leistungsbezogenen Faktoren hinter sportlichen Spitzenleistungen besser verstehen möchten. 

## Learnings
- Benutzung von Postman um Daten anzusehen, welche einen API-Key benötigen.
- Vorgängige Überprüfung, ob bei API alle Daten vorhanden sind (in unserem Fall, ob bei allen Teams auch die Spiele erfasst sind.)
- sftp.json nicht auf Github pushen aus Datenschutzgründen
- Wir können nun einen Algorithmus  programmieren
- Wir haben gelernt, wie man Daten über PHP verknüpft.

## Schwierigkeiten
- **Unsere API blockierte die Server-IP von Beni nach einer gewissen Anzahl von Anfragen.** Dadurch mussten wir, nach der Kontrolle, ob der Code wirklich funktioniert, durch Beni auf eine Alternativlösung ausweichen. Beni hat uns einen neuen Server eingerichtet und geraten, wie folgt fortzufahren: *Passenden API-Link und X-Auth-Token in Postman angeben -> anhand gewünschter Parameter filtern -> gesuchten Datensatz kopieren -> über ChatGPT den Datensatz zu einem SQL-Code zusammensetzen lassen -> über phpMyAdmin in die Datenbank einfügen.* Dadurch haben wir alle relevanten Daten in der Datenbank, müssen aber zukünftige Spiele von Hand aktualisieren. Aktualisiert werden von unserer Seite aus alle Spiele bis zur Abgabe am 18.10.24. Da die verglichenen Spieldaten sonst zu gering wären, haben wir nicht nur den Start der aktuellen Saison genommen, sondern alle Spiele der Mannschaften, die seit Jahresbeginn stattgefunden haben. **(Beni meinte, wir sollen hier noch referenzieren, welcher Code ursprünglich funktioniert hat: (ETL hat funktioniert)**
  
- Den Datensatz vor der Benutzung genau überprüfen, da bei kleineren Ligen, wie beispielsweise der Super League, teilweise Spiele fehlen, obwohl dies von der API nirgends dokumentiert wird.

- Darstellung der Diagramme war eine Herausforderung, und wir konnten nicht von Beginn an unsere Daten so veranschaulichen, dass sie auch aussagekräftig sind.

## Benutzte Ressourcen
- Postman um Daten nach Parametern zu filtern und mit X-Auth-Token entschlüsseln zu können.
- ChatGPT 4o
- Figma für Design und Layout
- W3Schools Tutorials für Code Verständnis
