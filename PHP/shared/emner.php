<?php
/**
 * Shared - Emner
 *
 * Rolle-basert emne-administrasjon:
 * - Student: Se registrerte emner (read-only)
 * - Foreleser: Se egne emner, opprette nye
 * - Admin: Se alle emner, administrere
 *
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/BaseController.php';
require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/db.php';

// Bruk BaseController
$controller = new BaseController();
$bruker = $controller->bruker;
$rolle = $controller->rolle;
$bruker_id = $bruker['user_id'];
$pdo = $controller->pdo;

require_once '../utility/db.php';
$pdo = getDB();

$feilmelding = '';
$suksessmelding = '';

// ====================================================================
// ADMIN & FORELESER: Sletting av emne
// ====================================================================

if (in_array($rolle, ['admin', 'foreleser']) && isset($_GET['slett']) && is_numeric($_GET['slett'])) {
    $emne_id = $_GET['slett'];
    
    // For foreleser: Sjekk at de eier emnet
    if ($rolle === 'foreleser') {
        $stmt = $pdo->prepare("SELECT * FROM emne WHERE emnekode = ? AND bruker_user_id = ?");
        $stmt->execute([$emne_id, $bruker_id]);
        if (!$stmt->fetch()) {
            $feilmelding = 'Du kan ikke slette andres emner.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM emne WHERE emnekode = ?");
            if ($stmt->execute([$emne_id])) {
                $suksessmelding = 'Emnet ble slettet.';
            } else {
                $feilmelding = 'Kunne ikke slette emnet.';
            }
        }
    } else {
        // Admin: Slett uten sjekk
        $stmt = $pdo->prepare("DELETE FROM emne WHERE emnekode = ?");
        if ($stmt->execute([$emne_id])) {
            $suksessmelding = 'Emnet ble slettet.';
        } else {
            $feilmelding = 'Kunne ikke slette emnet.';
        }
    }
}

// ====================================================================
// FORELESER: Opprett nytt emne
// ====================================================================

if ($rolle === 'foreleser' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opprett_emne'])) {
    $emnekode = $_POST['emnekode'] ?? '';
    $emnenavn = $_POST['emnenavn'] ?? '';
    $pin_kode = $_POST['pin_kode'] ?? '';

    if (empty($emnekode) || empty($emnenavn) || empty($pin_kode)) {
        $feilmelding = 'Alle felt må fylles ut.';
    } elseif (strlen($pin_kode) !== 4 || !ctype_digit($pin_kode)) {
        $feilmelding = 'PIN-koden må være nøyaktig 4 siffer.';
    } else {
        // Sjekk om emnekoden allerede finnes
        $stmt = $pdo->prepare("SELECT * FROM emne WHERE emnekode = ?");
        $stmt->execute([$emnekode]);

        if ($stmt->rowCount() > 0) {
            $feilmelding = 'Emnekoden er allerede registrert.';
        } else {
            // Opprett emne
            $stmt = $pdo->prepare("INSERT INTO emne (emnekode, emnenavn, bruker_user_id, pin_kode, innhold) VALUES (?, ?, ?, ?, '')");
            if ($stmt->execute([$emnekode, $emnenavn, $bruker_id, $pin_kode])) {
                $suksessmelding = 'Emnet er opprettet!';
                $_POST = []; // Clear form
            } else {
                $feilmelding = 'Kunne ikke opprette emnet. Prøv igjen.';
            }
        }
    }
}

// ====================================================================
// HENT EMNER
// ====================================================================

$emner = [];

if ($rolle === 'student') {
    // Student: Kun registrerte emner
    $stmt = $pdo->prepare("SELECT e.*, b.navn as foreleser_navn 
                           FROM emne e 
                           JOIN bruker_has_emne bhe ON e.emnekode = bhe.emne_emnekode 
                           LEFT JOIN bruker b ON e.bruker_user_id = b.user_id
                           WHERE bhe.bruker_user_id = ? 
                           ORDER BY e.emnekode");
    $stmt->execute([$bruker_id]);
    $emner = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($rolle === 'foreleser') {
    // Foreleser: Egne emner
    $stmt = $pdo->prepare("SELECT * FROM emne WHERE bruker_user_id = ? ORDER BY emnekode");
    $stmt->execute([$bruker_id]);
    $emner = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($rolle === 'admin') {
    // Admin: Alle emner
    $stmt = $pdo->query("SELECT e.*, b.navn as foreleser_navn 
                         FROM emne e 
                         LEFT JOIN bruker b ON e.bruker_user_id = b.user_id 
                         ORDER BY e.emnekode");
    $emner = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ====================================================================
// NAVIGASJON
// ====================================================================

$side_tittel = $rolle === 'student' ? 'Mine emner' : ($rolle === 'foreleser' ? 'Mine emner - Foreleser' : 'Emner | Admin');
echo Template::header($side_tittel, $rolle, Template::genererNav($rolle));
?>

<?php if ($rolle === 'admin'): ?>
    <div class="admin-innhold">
        <h2>Emne-administrasjon</h2>

        <?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

        <?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

        <section class="emne-liste" aria-label="Liste over emner">
            <?php if (empty($emner)): ?>
                <div class="ingen-emner" role="status">
                    <p style="color: #aaa;">Ingen emner funnet</p>
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #2a2a4a; text-align: left;">
                            <th style="padding: 1rem; border: 1px solid #3a3a5a;">Emnekode</th>
                            <th style="padding: 1rem; border: 1px solid #3a3a5a;">Emnenavn</th>
                            <th style="padding: 1rem; border: 1px solid #3a3a5a;">Foreleser</th>
                            <th style="padding: 1rem; border: 1px solid #3a3a5a;">PIN</th>
                            <th style="padding: 1rem; border: 1px solid #3a3a5a;">Handlinger</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emner as $emne): ?>
                            <tr style="background: #1a1a2e; border-bottom: 1px solid #2a2a4a;">
                                <td style="padding: 1rem; border: 1px solid #2a2a4a;"><?php echo htmlspecialchars($emne['emnekode']); ?></td>
                                <td style="padding: 1rem; border: 1px solid #2a2a4a;"><?php echo htmlspecialchars($emne['emnenavn']); ?></td>
                                <td style="padding: 1rem; border: 1px solid #2a2a4a;"><?php echo htmlspecialchars($emne['foreleser_navn'] ?? 'Ukjent'); ?></td>
                                <td style="padding: 1rem; border: 1px solid #2a2a4a;"><?php echo htmlspecialchars($emne['pin_kode']); ?></td>
                                <td style="padding: 1rem; border: 1px solid #2a2a4a;">
                                    <a href="?slett=<?php echo urlencode($emne['emnekode']); ?>" onclick="return confirm('Er du sikker på at du vil slette dette emnet?');" class="btn btn-liten" style="background: #e74c3c; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">Slett</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>

<?php elseif ($rolle === 'foreleser'): ?>

    <main class="hoved-innhold">
<header class="side-header">
    <h2>Mine emner</h2>
    <p>Administrer emnene du underviser i</p>
</header>

<?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

<?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

<section class="skjema-seksjon" aria-labelledby="skjema-tittel">
    <div class="info-boks">
        <h4>Opprett nytt emne</h4>
        <p>Studenter kan sende anonyme meldinger til emnet. PIN-koden brukes av gjester for å se meldinger offentlig.</p>
    </div>

    <form method="POST" novalidate>
        <h3 id="skjema-tittel" class="visually-hidden">Emneskjema</h3>

        <div class="skjema-rad">
            <div class="skjema-gruppe">
                <label for="emnekode">Emnekode</label>
                <input type="text" id="emnekode" name="emnekode" placeholder="DAT101" required>
                <p class="hjelp-tekst">F.eks. DAT101, INF200</p>
            </div>

            <div class="skjema-gruppe">
                <label for="pin_kode">PIN-kode (4 siffer)</label>
                <input type="text" id="pin_kode" name="pin_kode" placeholder="1234" maxlength="4" pattern="[0-9]{4}" inputmode="numeric" required>
                <p class="hjelp-tekst">Gjester bruker denne for å se meldinger</p>
            </div>
        </div>

        <div class="skjema-gruppe">
            <label for="emnenavn">Emnenavn</label>
            <input type="text" id="emnenavn" name="emnenavn" placeholder="Introduksjon til programmering" required>
            <p class="hjelp-tekst">Fullt navn på emnet</p>
        </div>

        <button type="submit" name="opprett_emne" class="btn btn-send">Opprett emne</button>
    </form>

    <?php if (!empty($emner)): ?>
        <section class="eksisterende-emner" aria-labelledby="eksisterende-tittel">
            <h3 id="eksisterende-tittel">Dine eksisterende emner (<?php echo count($emner); ?>)</h3>
            <div class="emne-liste">
                <?php foreach ($emner as $emne): ?>
                    <article class="emne-item">
                        <div class="emne-info">
                            <span class="emnekode"><?php echo htmlspecialchars($emne['emnekode']); ?></span>
                            <span class="emnenavn"><?php echo htmlspecialchars($emne['emnenavn']); ?></span>
                        </div>
                        <div class="emne-handlinger">
                            <span class="pin">PIN: <?php echo htmlspecialchars($emne['pin_kode']); ?></span>
                            <a href="?slett=<?php echo urlencode($emne['emnekode']); ?>" onclick="return confirm('Er du sikker på at du vil slette dette emnet?');" class="btn btn-danger btn-small">Slett</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
    </main>

<?php else: /* STUDENT VIEW */ ?>

    <main class="hoved-innhold">
<header class="side-header">
    <h2>Mine emner</h2>
    <p>Emner du er registrert i</p>
</header>

<?php if (empty($emner)): ?>
    <div class="ingen-emner" role="status">
        <p>Du er ikke registrert i noen emner ennå.</p>
    </div>
<?php else: ?>
    <section class="emne-grid" aria-label="Liste over emner">
        <?php foreach ($emner as $emne): ?>
            <article class="emne-kort">
                <h3><?php echo htmlspecialchars($emne['emnekode']); ?></h3>
                <p class="emnenavn"><?php echo htmlspecialchars($emne['emnenavn']); ?></p>
                <p class="foreleser">Foreleser: <?php echo htmlspecialchars($emne['foreleser_navn'] ?? 'Ukjent'); ?></p>
                <div class="emne-handlinger">
                    <a href="../shared/vis_emne.php?emnekode=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-primary">Se emne</a>
                    <a href="../shared/student_meldinger.php?tab=send&emne=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-secondary">Send melding</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
    </main>

<?php endif; ?>

<?php echo Template::footer(); ?>
