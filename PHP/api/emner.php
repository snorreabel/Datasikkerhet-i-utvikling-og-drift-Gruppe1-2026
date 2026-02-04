<?php
/**
 * API: Emner
 *
 * GET /api/emner.php - Hent alle emner (ingen autentisering)
 */

header('Content-Type: application/json');
require_once '../utility/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Kun GET er tillatt']);
    exit;
}

$pdo = getDB();

$stmt = $pdo->query("
    SELECT e.emnekode, e.emnenavn, b.navn as foreleser
    FROM emne e
    LEFT JOIN bruker b ON e.bruker_user_id = b.user_id
    ORDER BY e.emnekode
");
$emner = $stmt->fetchAll();

echo json_encode(['emner' => $emner]);
?>
