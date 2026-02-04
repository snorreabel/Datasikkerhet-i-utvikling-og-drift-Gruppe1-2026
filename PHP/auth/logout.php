<?php
/**
 * Felles utloggingsside
 * 
 * Håndterer utlogging for alle roller.
 * 
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

require_once __DIR__ . '/../utility/AuthService.php';

// Logger ut brukeren
AuthService::logout();

// Redirect til forsiden
header('Location: ../index.php');
exit;
?>
