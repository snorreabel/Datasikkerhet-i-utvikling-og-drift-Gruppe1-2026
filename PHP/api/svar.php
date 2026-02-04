<?php
/**
 * API: Svar (Foreleser svarer på melding)
 *
 * POST /api/svar.php
 * Body: {"melding_id": X, "innhold": "..."}
 */

session_start();
header('Content-Type: application/json');
require_once '../utility/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Kun POST er tillatt']);
    exit;
}

// Sjekk at bruker er innlogget som foreleser
if (!isset($_SESSION['user_id']) || $_SESSION['rolle'] !== 'foreleser') {
    http_response_code(401);
    echo json_encode(['error' => 'Kun innloggede forelesere kan svare']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$melding_id = $data['melding_id'] ?? 0;
$innhold = $data['innhold'] ?? '';

if (empty($melding_id) || empty($innhold)) {
    http_response_code(400);
    echo json_encode(['error' => 'Melding-ID og innhold er påkrevd']);
    exit;
}

$pdo = getDB();

// Sjekk at meldingen tilhører et emne foreleseren underviser
$stmt = $pdo->prepare("
    SELECT m.melding_id
    FROM melding m
    JOIN emne e ON m.emne_emnekode = e.emnekode
    WHERE m.melding_id = ? AND e.bruker_user_id = ?
");
$stmt->execute([$melding_id, $_SESSION['user_id']]);

if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Du har ikke tilgang til denne meldingen']);
    exit;
}

// Opprett svar som kommentar
$stmt = $pdo->prepare("INSERT INTO kommentar (innhold, tidspunkt, bruker_user_id, melding_id) VALUES (?, NOW(), ?, ?)");
$stmt->execute([$innhold, $_SESSION['user_id'], $melding_id]);

echo json_encode([
    'success' => true,
    'kommentar_id' => $pdo->lastInsertId()
]);
?>
