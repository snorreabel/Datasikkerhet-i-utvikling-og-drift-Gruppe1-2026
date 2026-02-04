<?php
define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/db.php';

// Sjekk om dette er gjest-tilgang via PIN
$er_gjest = isset($_GET['pin']);
$pin = $_GET['pin'] ?? '';
$bruker_id = null;
$rolle = 'gjest';
$bruker = null;
$pdo = getDB();

if ($er_gjest) {
    // Gjest-tilgang via PIN
    $rolle = 'gjest';
} else {
    // Sjekk innlogging - tillat både student og foreleser
    if (!AuthService::isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit;
    }

    // Hent innlogget bruker
    $bruker = AuthService::getUser();
    $bruker_id = $bruker['user_id'];
    $rolle = $bruker['rolle'];

    // Sjekk at rolle er gyldig
    if (!in_array($rolle, ['student', 'foreleser'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    
    // Innlogget bruker er ikke gjest, men kan se siden
    $er_gjest = false;
}

require_once '../utility/db.php';
$pdo = getDB();

$emnekode = $_GET['emnekode'] ?? '';
$emne = null;
$meldinger = [];
$suksessmelding = '';
$feilmelding = '';

// Håndter rapportering (gjester og innloggede brukere)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rapporter'])) {
    $melding_id = $_POST['melding_id'] ?? '';
    $beskrivelse = $_POST['grunn'] ?? '';

    // Knytt rapportering til bruker hvis innlogget
    if ($bruker_id) {
        $stmt = $pdo->prepare("INSERT INTO rapport (melding_melding_id, bruker_user_id, beskrivelse, tidspunkt) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$melding_id, $bruker_id, $beskrivelse]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO rapport (melding_melding_id, beskrivelse, tidspunkt) VALUES (?, ?, NOW())");
        $stmt->execute([$melding_id, $beskrivelse]);
    }
    
    if ($stmt->rowCount() > 0) {
        $suksessmelding = 'Meldingen er rapportert. Takk for tilbakemeldingen.';
    }
}

// Håndter kommentar (gjester og innloggede brukere)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['legg_til_kommentar'])) {
    $melding_id = $_POST['melding_id'] ?? '';
    $kommentar_tekst = trim($_POST['kommentar'] ?? '');
    $gjest_navn = trim($_POST['gjest_navn'] ?? 'Anonym gjest');

    if (!empty($melding_id) && !empty($kommentar_tekst)) {
        // Sjekk først om tabellen gjest_kommentar finnes, hvis ikke opprett den
        try {
            // Hvis innlogget bruker: knytt kommentar til bruker
            if ($bruker_id) {
                $stmt = $pdo->prepare("INSERT INTO gjest_kommentar (melding_melding_id, bruker_user_id, navn, innhold, tidspunkt) VALUES (?, ?, ?, ?, NOW())");
                if ($stmt->execute([$melding_id, $bruker_id, $gjest_navn, $kommentar_tekst])) {
                    $suksessmelding = 'Kommentaren din er lagt til.';
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO gjest_kommentar (melding_melding_id, navn, innhold, tidspunkt) VALUES (?, ?, ?, NOW())");
                if ($stmt->execute([$melding_id, $gjest_navn, $kommentar_tekst])) {
                    $suksessmelding = 'Kommentaren din er lagt til.';
                }
            }
        } catch (Exception $e) {
            // Tabellen finnes kanskje ikke, prøv å opprette den med bruker_user_id kolonne
            try {
                $pdo->query("CREATE TABLE IF NOT EXISTS gjest_kommentar (
                    kommentar_id INT AUTO_INCREMENT PRIMARY KEY,
                    melding_melding_id INT NOT NULL,
                    bruker_user_id INT NULL,
                    navn VARCHAR(100) DEFAULT 'Anonym gjest',
                    innhold TEXT NOT NULL,
                    tidspunkt DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                // Prøv å sette inn igjen
                if ($bruker_id) {
                    $stmt = $pdo->prepare("INSERT INTO gjest_kommentar (melding_melding_id, bruker_user_id, navn, innhold, tidspunkt) VALUES (?, ?, ?, ?, NOW())");
                    if ($stmt->execute([$melding_id, $bruker_id, $gjest_navn, $kommentar_tekst])) {
                        $suksessmelding = 'Kommentaren din er lagt til.';
                    }
                } else {
                    $stmt = $pdo->prepare("INSERT INTO gjest_kommentar (melding_melding_id, navn, innhold, tidspunkt) VALUES (?, ?, ?, NOW())");
                    if ($stmt->execute([$melding_id, $gjest_navn, $kommentar_tekst])) {
                        $suksessmelding = 'Kommentaren din er lagt til.';
                    }
                }
            } catch (Exception $e2) {
                $feilmelding = 'Kunne ikke legge til kommentar.';
            }
        }
    } else {
        $feilmelding = 'Vennligst skriv en kommentar.';
    }
}

// Hent emneinfo
if ($er_gjest) {
    // Gjest: Hent emne basert på PIN
    $stmt = $pdo->prepare("SELECT e.*, b.navn as foreleser_navn FROM emne e LEFT JOIN bruker b ON e.bruker_user_id = b.user_id WHERE e.pin_kode = ?");
    $stmt->execute([$pin]);
    $emne = $stmt->fetch();

    if (!$emne) {
        header('Location: ../index.php');
        exit;
    }

    // Sett emnekode fra emne
    $emnekode = $emne['emnekode'];
} else {
    if (empty($emnekode)) {
        header('Location: ../' . $rolle . '/dashboard.php');
        exit;
    }

    // Innlogget bruker: Hent emne normalt
    $stmt = $pdo->prepare("SELECT * FROM emne WHERE emnekode = ?");
    $stmt->execute([$emnekode]);
    $emne = $stmt->fetch();

    if (!$emne) {
        header('Location: ../shared/dashboard.php');
        exit;
    }

    // For foreleser: Sjekk at de eier emnet
    if ($rolle === 'foreleser' && $emne['bruker_user_id'] != $bruker_id) {
        header('Location: ../shared/dashboard.php');
        exit;
    }

    // For student: Sjekk at de er registrert i emnet
    if ($rolle === 'student') {
        $stmt = $pdo->prepare("SELECT * FROM bruker_has_emne WHERE bruker_user_id = ? AND emne_emnekode = ?");
        $stmt->execute([$bruker_id, $emnekode]);
        if (!$stmt->fetch()) {
            header('Location: ../shared/dashboard.php');
            exit;
        }
    }
}

// Hent alle meldinger for emnet med brukerinfo og kommentarer
$stmt = $pdo->prepare("
    SELECT
        m.melding_id,
        m.innhold,
        m.tidspunkt,
        m.bruker_user_id,
        b.navn as bruker_navn,
        k.kommentar_id,
        k.innhold as kommentar_innhold,
        k.bruker_user_id as kommentar_bruker_id,
        kb.navn as kommentar_bruker_navn
    FROM melding m
    LEFT JOIN bruker b ON m.bruker_user_id = b.user_id
    LEFT JOIN kommentar k ON k.melding_id = m.melding_id
    LEFT JOIN bruker kb ON k.bruker_user_id = kb.user_id
    WHERE m.emne_emnekode = ?
    ORDER BY m.tidspunkt DESC
");
$stmt->execute([$emnekode]);
$meldinger = $stmt->fetchAll();

// Hent gjestekommentarer for hver melding
$gjest_kommentarer = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM gjest_kommentar WHERE melding_melding_id IN (SELECT melding_id FROM melding WHERE emne_emnekode = ?) ORDER BY tidspunkt ASC");
    $stmt->execute([$emnekode]);
    $alle_gjest_kommentarer = $stmt->fetchAll();
    foreach ($alle_gjest_kommentarer as $gk) {
        $gjest_kommentarer[$gk['melding_melding_id']][] = $gk;
    }
} catch (Exception $e) {
    // Tabellen finnes ikke ennå, ignorer
}

// Sett opp navigasjon basert på rolle
if ($er_gjest) {
    $navigasjon = [
        ['url' => '../index.php', 'tekst' => 'Tilbake', 'klasse' => 'btn-secondary'],
        ['url' => '../auth/registrer.php?rolle=student', 'tekst' => 'Registrer deg', 'klasse' => 'btn-registrer'],
        ['url' => '../auth/login.php?rolle=student', 'tekst' => 'Logg inn', 'klasse' => 'btn-logginn']
    ];
} else {
    // Bruk sentralisert navigasjon for innloggede brukere
    $navigasjon = Template::genererNav($rolle);
}

echo Template::header('Vis emne - ' . htmlspecialchars($emne['emnenavn']), $rolle, $navigasjon);
?>

<?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

<?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

        <section class="emne-header">
            <div class="emne-info">
                <span class="emnekode"><?php echo htmlspecialchars($emne['emnekode']); ?></span>
                <h2><?php echo htmlspecialchars($emne['emnenavn']); ?></h2>
                <?php if ($rolle === 'foreleser'): ?>
                    <p>PIN-kode: <strong><?php echo htmlspecialchars($emne['pin_kode']); ?></strong></p>
                <?php elseif ($er_gjest && !empty($emne['foreleser_navn'])): ?>
                    <p>Foreleser: <strong><?php echo htmlspecialchars($emne['foreleser_navn']); ?></strong></p>
                <?php endif; ?>
            </div>
            <?php if ($rolle === 'student'): ?>
                <a href="../shared/student_meldinger.php?tab=send&emne=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-primary">Send ny melding</a>
            <?php endif; ?>
        </section>

        <section class="meldinger-seksjon">
            <h3><?php echo ($rolle === 'foreleser') ? 'Meldinger fra studenter' : 'Meldinger'; ?></h3>

            <?php if (empty($meldinger)): ?>
                <div class="ingen-innhold">
                    <p>Ingen meldinger ennå i dette emnet.</p>
                    <?php if ($rolle === 'student'): ?>
                        <a href="../shared/student_meldinger.php?tab=send&emne=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-primary">Send første melding</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="meldinger-liste">
                    <?php foreach ($meldinger as $melding): ?>
                        <article class="melding-kort">
                            <div class="melding-header">
                                <?php
                                // Vis avsender basert på rolle
                                if ($rolle === 'foreleser') {
                                    // Foreleser ser alltid "Anonym student"
                                    echo '<span class="melding-fra">Anonym student</span>';
                                } elseif ($er_gjest) {
                                    // Gjest ser alltid "Anonym student"
                                    echo '<span class="melding-fra">Anonym student</span>';
                                } else {
                                    // Student ser "Din melding" eller "Anonym student"
                                    if ($melding['bruker_user_id'] == $bruker_id) {
                                        echo '<span class="melding-fra">Din melding</span>';
                                    } else {
                                        echo '<span class="melding-fra">Anonym student</span>';
                                    }
                                }
                                ?>
                                <span class="melding-dato"><?php echo date('d.m.Y H:i', strtotime($melding['tidspunkt'])); ?></span>
                            </div>
                            <div class="melding-innhold">
                                <p><?php echo nl2br(htmlspecialchars($melding['innhold'])); ?></p>
                            </div>

                            <?php if (!empty($melding['kommentar_innhold'])): ?>
                                <div class="svar-seksjon">
                                    <div class="svar-header">
                                        <strong><?php echo ($rolle === 'foreleser') ? 'Ditt svar' : 'Svar fra foreleser'; ?></strong>
                                        <?php if ($rolle === 'student' && !empty($melding['kommentar_bruker_navn'])): ?>
                                            <span><?php echo htmlspecialchars($melding['kommentar_bruker_navn']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="svar-innhold">
                                        <p><?php echo nl2br(htmlspecialchars($melding['kommentar_innhold'])); ?></p>
                                    </div>
                                    <?php if ($rolle === 'foreleser'): ?>
                                        <div style="margin-top: 1rem;">
                                            <a href="../foreleser/svar.php?melding_id=<?php echo $melding['melding_id']; ?>" class="btn btn-secondary btn-small">Rediger svar</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="svar-seksjon venter">
                                    <p class="venter-tekst"><?php echo ($rolle === 'foreleser') ? 'Ikke besvart ennå' : 'Venter på svar fra foreleser'; ?></p>
                                    <?php if ($rolle === 'foreleser'): ?>
                                        <div style="margin-top: 1rem;">
                                            <a href="../foreleser/svar.php?melding_id=<?php echo $melding['melding_id']; ?>" class="btn btn-primary btn-small">Svar på melding</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Gjestekommentarer -->
                            <?php if (!empty($gjest_kommentarer[$melding['melding_id']])): ?>
                                <div class="gjest-kommentarer" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ddd;">
                                    <h4 style="font-size: 0.9rem; color: #666; margin-bottom: 0.5rem;">Kommentarer fra gjester:</h4>
                                    <?php foreach ($gjest_kommentarer[$melding['melding_id']] as $gk): ?>
                                        <div class="gjest-kommentar" style="background: #f9f9f9; padding: 0.75rem; border-radius: 4px; margin-bottom: 0.5rem;">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                                <strong style="color: #27ae60; font-size: 0.85rem;"><?php echo htmlspecialchars($gk['navn']); ?></strong>
                                                <span style="color: #999; font-size: 0.8rem;"><?php echo date('d.m.Y H:i', strtotime($gk['tidspunkt'])); ?></span>
                                            </div>
                                            <p style="margin: 0; color: #444; font-size: 0.9rem;"><?php echo nl2br(htmlspecialchars($gk['innhold'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Gjestehandlinger: kommentar og rapportering -->
                            <?php if ($er_gjest): ?>
                                <div class="gjest-handlinger" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                                    <!-- Legg til kommentar -->
                                    <details style="margin-bottom: 0.5rem;">
                                        <summary style="cursor: pointer; color: #27ae60; font-weight: 500;">Legg til kommentar</summary>
                                        <form method="post" style="margin-top: 0.5rem; padding: 1rem; background: #f8f9fa; border-radius: 4px;">
                                            <input type="hidden" name="melding_id" value="<?php echo $melding['melding_id']; ?>">
                                            <div style="margin-bottom: 0.5rem;">
                                                <label for="gjest_navn_<?php echo $melding['melding_id']; ?>" style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem;">Ditt navn (valgfritt):</label>
                                                <input type="text" id="gjest_navn_<?php echo $melding['melding_id']; ?>" name="gjest_navn" placeholder="Anonym gjest" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                            </div>
                                            <div style="margin-bottom: 0.5rem;">
                                                <label for="kommentar_<?php echo $melding['melding_id']; ?>" style="display: block; margin-bottom: 0.25rem; font-size: 0.9rem;">Din kommentar:</label>
                                                <textarea id="kommentar_<?php echo $melding['melding_id']; ?>" name="kommentar" rows="3" placeholder="Skriv din kommentar her..." style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" required></textarea>
                                            </div>
                                            <button type="submit" name="legg_til_kommentar" class="btn btn-primary btn-small" style="background: #27ae60; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Send kommentar</button>
                                        </form>
                                    </details>

                                    <!-- Rapporter melding -->
                                    <details>
                                        <summary style="cursor: pointer; color: #e74c3c; font-weight: 500;">Rapporter melding</summary>
                                        <form method="post" style="margin-top: 0.5rem; padding: 1rem; background: #fff3f3; border-radius: 4px;">
                                            <input type="hidden" name="melding_id" value="<?php echo $melding['melding_id']; ?>">
                                            <label for="grunn_<?php echo $melding['melding_id']; ?>" style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;">Beskriv hvorfor du rapporterer:</label>
                                            <textarea name="grunn" id="grunn_<?php echo $melding['melding_id']; ?>" rows="2" placeholder="Beskriv hvorfor du rapporterer denne meldingen..." style="width: 100%; padding: 0.5rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 0.5rem;"></textarea>
                                            <button type="submit" name="rapporter" class="btn btn-secondary btn-small" style="background: #e74c3c; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Rapporter melding</button>
                                        </form>
                                    </details>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

<?php echo Template::footer(); ?>
