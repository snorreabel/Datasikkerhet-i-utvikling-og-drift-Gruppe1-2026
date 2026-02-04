<?php
/**
 * Foreleser - Svar på melding
 *
 * Side for å svare på en spesifikk melding fra en student.
 * Kun ett svar per melding er tillatt.
 *
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/BaseController.php';
require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/db.php';

// Bruk BaseController - tillat kun foreleser
$controller = new BaseController(['foreleser']);
$bruker = $controller->bruker;
$bruker_id = $bruker['user_id'];
$pdo = $controller->pdo;

$melding_id = $_GET['id'] ?? '';
$feilmelding = '';
$suksessmelding = '';
$melding = null;

// Hent meldingen
if ($melding_id) {
    $sql = "SELECT m.*, e.emnekode, e.emnenavn, e.bruker_user_id as foreleser_id, 
                   k.innhold as eksisterende_svar, k.kommentar_id
            FROM melding m
            JOIN emne e ON m.emne_emnekode = e.emnekode
            LEFT JOIN kommentar k ON k.melding_id = m.melding_id
            WHERE m.melding_id = '$melding_id'";
    $res = $pdo->query($sql);

    if ($res && $melding = $res->fetch(PDO::FETCH_ASSOC)) {

        // Sjekk at meldingen tilhører foreleserens emne
        if ($melding['foreleser_id'] != $bruker_id) {
            $feilmelding = 'Du har ikke tilgang til denne meldingen.';
            $melding = null;
        }
    } else {
        $feilmelding = 'Meldingen ble ikke funnet.';
    }
}

// Håndter innsending av svar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $melding) {
    $svar = $_POST['svar'] ?? '';

    if (empty($svar)) {
        $feilmelding = 'Vennligst skriv et svar.';
    } elseif (!empty($melding['eksisterende_svar'])) {
        $feilmelding = 'Denne meldingen er allerede besvart.';
    } else {
        // Opprett ny kommentar som svar på meldingen
        $sql = "INSERT INTO kommentar (innhold, tidspunkt, bruker_user_id, melding_id) VALUES ('$svar', NOW(), '$bruker_id', '$melding_id')";

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute()) {
            $suksessmelding = 'Svaret ditt er sendt!';
            $melding['eksisterende_svar'] = $svar;
        } else {
            $feilmelding = 'Kunne ikke sende svaret. Prøv igjen.';
        }
    }
}

echo Template::header('Svar på melding - Foreleser', 'foreleser', Template::genererNav('foreleser'));
?>

        <header class="side-header">
                <a href="meldinger.php" class="btn btn-secondary">Tilbake</a>
                <h2>Svar på melding</h2>
            </header>

            <?php
            if ($feilmelding) Template::visFeil($feilmelding);
            if ($suksessmelding) Template::visSuksess($suksessmelding);
            ?>

            <?php if ($melding): ?>
                <article class="melding-kort" aria-labelledby="melding-tittel">
                    <header class="melding-header">
                        <span class="emne-badge" id="melding-tittel"><?php echo htmlspecialchars($melding['emnekode']); ?> - <?php echo htmlspecialchars($melding['emnenavn']); ?></span>
                        <time class="melding-dato" datetime="<?php echo $melding['tidspunkt']; ?>">Mottatt: <?php echo date('d.m.Y H:i', strtotime($melding['tidspunkt'])); ?></time>
                    </header>

                    <div class="melding-innhold">
                        <h3>Melding fra student (anonym)</h3>
                        <p><?php echo nl2br(htmlspecialchars($melding['innhold'])); ?></p>
                    </div>

                    <?php if (!empty($melding['eksisterende_svar'])): ?>
                        <aside class="eksisterende-svar" aria-label="Ditt svar">
                            <h3>Ditt svar</h3>
                            <p><?php echo nl2br(htmlspecialchars($melding['eksisterende_svar'])); ?></p>
                        </aside>
                    <?php endif; ?>
                </article>

                <?php if (empty($melding['eksisterende_svar'])): ?>
                    <section class="svar-seksjon" aria-labelledby="svar-tittel">
                        <h2 id="svar-tittel">Skriv ditt svar</h2>
                        <form method="POST" novalidate>
                            <fieldset>
                                <legend class="visually-hidden">Svarskjema</legend>
                                <div class="skjema-gruppe">
                                    <label for="svar">Ditt svar til studenten</label>
                                    <textarea id="svar" name="svar" placeholder="Skriv ditt svar her..." required aria-describedby="svar-hjelp"></textarea>
                                    <p class="hjelp-tekst" id="svar-hjelp">Studenten vil kunne se svaret ditt, men du vil fortsatt ikke vite hvem som sendte meldingen.</p>
                                </div>
                            </fieldset>

                            <button type="submit" class="btn btn-success btn-send">Send svar</button>
                        </form>
                    </section>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; margin-top: 1rem;">
                        Du har allerede svart på denne meldingen.
                        <a href="meldinger.php">Tilbake til meldinger</a>
                    </p>
                <?php endif; ?>

            <?php elseif (!$feilmelding): ?>
                <div class="feilmelding" role="alert">Ingen melding spesifisert.</div>
                <p style="text-align: center; margin-top: 1rem;">
                    <a href="meldinger.php" class="btn btn-secondary">Tilbake til meldinger</a>
                </p>
            <?php endif; ?>

<?php echo Template::footer(); ?>
