<?php
/**
 * API: Meldinger
 *
 * GET  /api/meldinger.php?emne=XX&pin=XXXX  - Hent meldinger for emne (krever PIN)
 * POST /api/meldinger.php                    - Send melding (krever innlogging som student)
 */

session_start();
header('Content-Type: application/json');
require_once '../utility/db.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET: Hent meldinger for et emne (gjest med PIN)
if ($method === 'GET') {
    $emnekode = $_GET['emne'] ?? '';
    $pin = $_GET['pin'] ?? '';

    if (empty($emnekode) || empty($pin)) {
        http_response_code(400);
        echo json_encode(['error' => 'Emnekode og PIN er påkrevd']);
        exit;
    }

    // Verifiser PIN
    $stmt = $pdo->prepare("SELECT emnekode, emnenavn, bruker_user_id FROM emne WHERE emnekode = ? AND pin_kode = ?");
    $stmt->execute([$emnekode, $pin]);
    $emne = $stmt->fetch();

    if (!$emne) {
        http_response_code(401);
        echo json_encode(['error' => 'Ugyldig emnekode eller PIN']);
        exit;
    }

    // Hent foreleser-info
    $stmt = $pdo->prepare("SELECT navn FROM bruker WHERE user_id = ?");
    $stmt->execute([$emne['bruker_user_id']]);
    $foreleser = $stmt->fetch();

    // Hent meldinger
    $stmt = $pdo->prepare("SELECT melding_id, innhold, tidspunkt FROM melding WHERE emne_emnekode = ? ORDER BY tidspunkt DESC");
    $stmt->execute([$emnekode]);
    $meldinger = $stmt->fetchAll();

    echo json_encode([
        'emne' => [
            'emnekode' => $emne['emnekode'],
            'emnenavn' => $emne['emnenavn'],
            'foreleser' => $foreleser['navn'] ?? 'Ukjent'
        ],
        'meldinger' => $meldinger
    ]);
}

// POST: Send melding (student)
elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || $_SESSION['rolle'] !== 'student') {
        http_response_code(401);
        echo json_encode(['error' => 'Kun innloggede studenter kan sende meldinger']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $emnekode = $data['emnekode'] ?? '';
    $innhold = $data['innhold'] ?? '';

    if (empty($emnekode) || empty($innhold)) {
        http_response_code(400);
        echo json_encode(['error' => 'Emnekode og innhold er påkrevd']);
        exit;
    }

    // Sjekk at emnet finnes
    $stmt = $pdo->prepare("SELECT emnekode FROM emne WHERE emnekode = ?");
    $stmt->execute([$emnekode]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Emnet finnes ikke']);
        exit;
    }

    // Sett inn melding
    $stmt = $pdo->prepare("INSERT INTO melding (emne_emnekode, bruker_user_id, innhold, tidspunkt) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$emnekode, $_SESSION['user_id'], $innhold]);

    echo json_encode([
        'success' => true,
        'melding_id' => $pdo->lastInsertId()
    ]);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Metode ikke tillatt']);
}
?>
