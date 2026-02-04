<?php
/**
 * API - Login
 *
 * Autentiserer bruker og returnerer JSON-respons.
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../utility/AuthService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Kun POST-forespørsler er tillatt', 'code' => 405]);
    exit;
}

$epost = $_POST['epost'] ?? '';
$passord = $_POST['passord'] ?? '';
$rolle = $_POST['rolle'] ?? 'student';

if (empty($epost) || empty($passord)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'E-post og passord er påkrevd', 'code' => 400]);
    exit;
}

$resultat = AuthService::login($epost, $passord, $rolle);

if ($resultat['success']) {
    echo json_encode([
        'success' => true,
        'message' => 'Innlogging vellykket',
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'navn' => $_SESSION['navn'],
            'epost' => $_SESSION['epost'],
            'rolle' => $_SESSION['rolle']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => $resultat['message'], 'code' => 401]);
}
