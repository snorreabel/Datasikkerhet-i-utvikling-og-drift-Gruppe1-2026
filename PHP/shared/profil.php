<?php
/**
 * Shared - Profil
 *
 * Rolle-basert profil og innstillinger:
 * - Alle roller: Se profil, endre passord, oppdatere kontaktinfo
 * - Foreleser: Se profilbilde (lastes opp ved registrering)
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

$feilmelding = '';
$suksessmelding = '';
$aktiv_fane = $_GET['fane'] ?? 'profil';

// ====================================================================
// HÅNDTER PASSORDENDRING
// ====================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['endre_passord'])) {
    $gammelt_passord = $_POST['gammelt_passord'] ?? '';
    $nytt_passord = $_POST['nytt_passord'] ?? '';
    $bekreft_passord = $_POST['bekreft_passord'] ?? '';

    if (empty($gammelt_passord) || empty($nytt_passord) || empty($bekreft_passord)) {
        $feilmelding = 'Alle felt må fylles ut.';
    } elseif ($nytt_passord !== $bekreft_passord) {
        $feilmelding = 'Nytt passord og bekreftelse stemmer ikke overens.';
    } elseif (strlen($nytt_passord) < 6) {
        $feilmelding = 'Nytt passord må være minst 6 tegn.';
    } else {
        $stmt = $pdo->prepare("SELECT passord FROM bruker WHERE user_id = ?");
        $stmt->execute([$bruker_id]);
        $db_bruker = $stmt->fetch();

        if ($db_bruker && password_verify($gammelt_passord, $db_bruker['passord'])) {
            $nytt_hash = password_hash($nytt_passord, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE bruker SET passord = ? WHERE user_id = ?");

            if ($stmt->execute([$nytt_hash, $bruker_id])) {
                $suksessmelding = 'Passordet ble endret!';
            } else {
                $feilmelding = 'Kunne ikke oppdatere passord.';
            }
        } else {
            $feilmelding = 'Gammelt passord er feil.';
        }
    }
    $aktiv_fane = 'passord';
}

// ====================================================================
// HÅNDTER PROFILINFO-OPPDATERING
// ====================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oppdater_profil'])) {
    $navn = trim($_POST['navn'] ?? '');
    $epost = trim($_POST['epost'] ?? '');

    if (empty($navn) || empty($epost)) {
        $feilmelding = 'Navn og e-post må fylles ut.';
    } elseif (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
        $feilmelding = 'Ugyldig e-postadresse.';
    } else {
        $stmt = $pdo->prepare("UPDATE bruker SET navn = ?, epost = ? WHERE user_id = ?");
        if ($stmt->execute([$navn, $epost, $bruker_id])) {
            $suksessmelding = 'Profilen ble oppdatert!';
            // Oppdater session
            $_SESSION['bruker']['navn'] = $navn;
            $_SESSION['bruker']['epost'] = $epost;
            $bruker = AuthService::getUser();
        } else {
            $feilmelding = 'Kunne ikke oppdatere profilen.';
        }
    }
    $aktiv_fane = 'profil';
}

// ====================================================================
// HENT FORELESERENS PROFILBILDE (hvis foreleser)
// ====================================================================

$foreleser_bilde = null;
if ($rolle === 'foreleser') {
    $bilder_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bilder' . DIRECTORY_SEPARATOR;
    $bilder_pattern = $bilder_dir . 'foreleser_' . $bruker_id . '_*';
    $bilder = glob($bilder_pattern);

    if (!empty($bilder)) {
        // Hent nyeste bilde (sist i lista sortert etter timestamp)
        usort($bilder, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $foreleser_bilde = '../assets/bilder/' . basename($bilder[0]);
    }
}

// ====================================================================
// HENT STUDENTINFO (hvis student)
// ====================================================================

$student_info = null;
if ($rolle === 'student') {
    $stmt = $pdo->prepare("SELECT * FROM student_info WHERE bruker_user_id = ?");
    $stmt->execute([$bruker_id]);
    $student_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ====================================================================
// NAVIGASJON
// ====================================================================

$navigasjon = Template::genererNav($rolle);
echo Template::header('Min profil', $rolle, $navigasjon);
?>

<div class="container">
    <header class="side-header">
        <h2>Min profil</h2>
        <p>Administrer profilinformasjon og innstillinger</p>
    </header>

    <?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

    <?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

    <!-- FANER -->
    <nav class="profil-faner" style="display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid #2a2a4a;">
        <a href="?fane=profil" class="fane-knapp <?php echo $aktiv_fane === 'profil' ? 'aktiv' : ''; ?>" style="padding: 1rem 1.5rem; text-decoration: none; color: <?php echo $aktiv_fane === 'profil' ? '#3498db' : '#aaa'; ?>; border-bottom: 3px solid <?php echo $aktiv_fane === 'profil' ? '#3498db' : 'transparent'; ?>;">
            Profilinformasjon
        </a>
        <a href="?fane=passord" class="fane-knapp <?php echo $aktiv_fane === 'passord' ? 'aktiv' : ''; ?>" style="padding: 1rem 1.5rem; text-decoration: none; color: <?php echo $aktiv_fane === 'passord' ? '#3498db' : '#aaa'; ?>; border-bottom: 3px solid <?php echo $aktiv_fane === 'passord' ? '#3498db' : 'transparent'; ?>;">
            Endre passord
        </a>
    </nav>

    <!-- PROFILINFORMASJON -->
    <?php if ($aktiv_fane === 'profil'): ?>
        <section class="profil-seksjon">
            <h3>Profilinformasjon</h3>

            <div class="profil-visning" style="background: #1a1a2e; padding: 2rem; border-radius: 8px; margin-bottom: 2rem; display: flex; gap: 2rem; align-items: flex-start;">
                <?php if ($rolle === 'foreleser' && $foreleser_bilde): ?>
                    <div class="profil-bilde" style="flex-shrink: 0;">
                        <img src="<?php echo htmlspecialchars($foreleser_bilde); ?>"
                             alt="Profilbilde"
                             style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #9b59b6;">
                    </div>
                <?php endif; ?>

                <dl style="display: grid; grid-template-columns: 150px 1fr; gap: 1rem; flex-grow: 1;">
                    <dt style="color: #aaa; font-weight: bold;">Navn:</dt>
                    <dd style="color: #eee;"><?php echo htmlspecialchars($bruker['navn']); ?></dd>

                    <dt style="color: #aaa; font-weight: bold;">E-post:</dt>
                    <dd style="color: #eee;"><?php echo htmlspecialchars($bruker['epost']); ?></dd>

                    <dt style="color: #aaa; font-weight: bold;">Rolle:</dt>
                    <dd style="color: #eee;"><?php echo ucfirst($rolle); ?></dd>

                    <?php if ($student_info): ?>
                        <dt style="color: #aaa; font-weight: bold;">Studieretning:</dt>
                        <dd style="color: #eee;"><?php echo htmlspecialchars($student_info['studieretning'] ?? 'Ikke angitt'); ?></dd>

                        <dt style="color: #aaa; font-weight: bold;">Kull:</dt>
                        <dd style="color: #eee;"><?php echo htmlspecialchars($student_info['kull'] ?? 'Ikke angitt'); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <form method="POST" class="skjema-seksjon">
                <h4>Oppdater profilinformasjon</h4>
                
                <div class="skjema-gruppe">
                    <label for="navn">Navn</label>
                    <input type="text" id="navn" name="navn" value="<?php echo htmlspecialchars($bruker['navn']); ?>" required>
                </div>

                <div class="skjema-gruppe">
                    <label for="epost">E-post</label>
                    <input type="email" id="epost" name="epost" value="<?php echo htmlspecialchars($bruker['epost']); ?>" required>
                </div>

                <button type="submit" name="oppdater_profil" class="btn btn-primary">Oppdater profil</button>
            </form>
        </section>
    <?php endif; ?>

    <!-- ENDRE PASSORD -->
    <?php if ($aktiv_fane === 'passord'): ?>
        <section class="passord-seksjon">
            <h3>Endre passord</h3>
            
            <form method="POST" class="skjema-seksjon" style="max-width: 500px;">
                <div class="skjema-gruppe">
                    <label for="gammelt_passord">Nåværende passord</label>
                    <input type="password" id="gammelt_passord" name="gammelt_passord" required>
                </div>

                <div class="skjema-gruppe">
                    <label for="nytt_passord">Nytt passord</label>
                    <input type="password" id="nytt_passord" name="nytt_passord" required>
                    <p class="skjema-hjelp">Minst 6 tegn</p>
                </div>

                <div class="skjema-gruppe">
                    <label for="bekreft_passord">Bekreft nytt passord</label>
                    <input type="password" id="bekreft_passord" name="bekreft_passord" required>
                </div>

                <button type="submit" name="endre_passord" class="btn btn-primary">Endre passord</button>
            </form>
        </section>
    <?php endif; ?>

</div>

<?php echo Template::footer(); ?>
