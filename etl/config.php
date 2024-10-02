<?php

// Datenbankverbindungsparameter
$host = 'etl.benhan51.dbs.hostpoint.internal';
$dbname = 'benhan51_etl';
$username = 'benhan51_etl';
$password = 'MMP2024_fhgr_etl_zuerich';

// DSN (Datenquellenname) für PDO
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";

// Optionen für PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Aktiviert die Ausnahmebehandlung für Datenbankfehler
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Legt den Standard-Abrufmodus auf assoziatives Array fest
    PDO::ATTR_EMULATE_PREPARES => false, // Deaktiviert die Emulation vorbereiteter Anweisungen, für bessere Leistung
];

?>