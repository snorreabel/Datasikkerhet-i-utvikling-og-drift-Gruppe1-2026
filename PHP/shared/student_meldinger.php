<?php
/**
 * Shared - Student meldinger
 *
 * Konsolidert side for studenter:
 * - Tab 1: Mine meldinger (oversikt over sendte meldinger og svar)
 * - Tab 2: Send ny melding (anonym melding til emne)
 *
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/BaseController.php';

// Sjekk innlogging - krever student-rolle
if (!AuthService::isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$bruker = AuthService::getUser();
$rolle = $bruker['rolle'] ?? null;

if ($rolle !== 'student') {
    header('Location: ../shared/dashboard.php');
    exit;
}

$bruker_id = $bruker['user_id'];

require_once '../utility/db.php';
$pdo = getDB();

$feilmelding = '';
$suksessmelding = '';
$active_tab = $_GET['tab'] ?? 'mine';
$valgt_emne = $_GET['emne'] ?? '';

// ====================================================================
// HÅNDTER INNSENDING AV NY MELDING
// ====================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_melding'])) {
    $emnekode = $_POST['emnekode'] ?? '';
    $innhold = $_POST['innhold'] ?? '';

    if (empty($emnekode) || empty($innhold)) {
        $feilmelding = 'Velg et emne og skriv en melding.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO melding (emne_emnekode, bruker_user_id, innhold, tidspunkt) VALUES (?, ?, ?, NOW())");
        if ($stmt->execute([$emnekode, $bruker_id, $innhold])) {
            $suksessmelding = 'Meldingen din har blitt sendt!';
            $active_tab = 'mine'; // Bytt til mine meldinger etter sending
        } else {
            $feilmelding = 'Noe gikk galt. Prøv igjen.';
        }
    }
}

// ====================================================================
// HENT DATA
// ====================================================================

// Hent alle emner for dropdown
$emner = $pdo->query("SELECT * FROM emne ORDER BY emnekode")->fetchAll(PDO::FETCH_ASSOC);

// Hent studentens meldinger med svar
$sql = "SELECT m.*, e.emnekode, e.emnenavn, k.innhold as svar, k.tidspunkt as svar_tidspunkt
        FROM melding m
        JOIN emne e ON m.emne_emnekode = e.emnekode
        LEFT JOIN kommentar k ON k.melding_id = m.melding_id
        WHERE m.bruker_user_id = ?
        ORDER BY m.tidspunkt DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$bruker_id]);
$meldinger = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistikk
$totalt = count($meldinger);
$med_svar = 0;
foreach ($meldinger as $melding) {
    if (!empty($melding['svar'])) {
        $med_svar++;
    }
}

// ====================================================================
// NAVIGASJON
// ====================================================================

echo Template::header('Meldinger - Student', 'student', Template::genererNav('student'));
?>

    <main class="hoved-innhold">
        <header class="side-header">
            <h2>Meldinger</h2>
            <p>Send anonyme meldinger til emner eller se dine tidligere meldinger</p>
        </header>

        <?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

        <?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

        <!-- Tab navigasjon -->
        <nav class="tab-navigasjon" role="tablist">
            <a href="?tab=mine" class="tab-link <?php echo $active_tab === 'mine' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'mine' ? 'true' : 'false'; ?>">Mine meldinger</a>
            <a href="?tab=send" class="tab-link <?php echo $active_tab === 'send' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'send' ? 'true' : 'false'; ?>">Send ny melding</a>
        </nav>

        <?php if ($active_tab === 'mine'): ?>
            <!-- MINE MELDINGER TAB -->
            <section class="tab-innhold" role="tabpanel">
                <section class="statistikk-linje" aria-label="Meldingsstatistikk">
                    <div class="stat-item">
                        <span class="tall"><?php echo $totalt; ?></span>
                        <span class="label">sendte meldinger</span>
                    </div>
                    <div class="stat-item svar">
                        <span class="tall"><?php echo $med_svar; ?></span>
                        <span class="label">med svar</span>
                    </div>
                    <div class="stat-item">
                        <span class="tall"><?php echo $totalt - $med_svar; ?></span>
                        <span class="label">venter på svar</span>
                    </div>
                </section>

                <section class="meldinger-liste" aria-label="Dine sendte meldinger">
                    <?php if (empty($meldinger)): ?>
                        <div class="ingen-meldinger" role="status">
                            <p>Du har ikke sendt noen meldinger ennå.</p>
                            <a href="?tab=send" class="btn btn-primary">Send din første melding</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($meldinger as $melding): ?>
                            <article class="melding-kort" aria-labelledby="melding-<?php echo $melding['melding_id']; ?>">
                                <header class="melding-header">
                                    <div class="emne-info">
                                        <span class="emnekode"><?php echo htmlspecialchars($melding['emnekode']); ?></span>
                                        <span class="emnenavn" id="melding-<?php echo $melding['melding_id']; ?>"><?php echo htmlspecialchars($melding['emnenavn']); ?></span>
                                    </div>
                                    <time class="melding-dato" datetime="<?php echo $melding['tidspunkt']; ?>"><?php echo date('d.m.Y H:i', strtotime($melding['tidspunkt'])); ?></time>
                                </header>

                                <div class="melding-innhold">
                                    <p><?php echo nl2br(htmlspecialchars($melding['innhold'])); ?></p>
                                </div>

                                <?php if (!empty($melding['svar'])): ?>
                                    <aside class="melding-svar" aria-label="Svar fra foreleser">
                                        <p class="svar-label">Svar fra foreleser<?php if (!empty($melding['svar_tidspunkt'])): ?> (<?php echo date('d.m.Y H:i', strtotime($melding['svar_tidspunkt'])); ?>)<?php endif; ?>:</p>
                                        <p><?php echo nl2br(htmlspecialchars($melding['svar'])); ?></p>
                                    </aside>
                                <?php else: ?>
                                    <div class="venter-svar" role="status">
                                        Venter på svar fra foreleseren...
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
            </section>

        <?php else: ?>
            <!-- SEND NY MELDING TAB -->
            <section class="tab-innhold" role="tabpanel">
                <section class="skjema-seksjon" aria-labelledby="skjema-tittel">
                    <aside class="anonymitet-info" aria-label="Informasjon om anonymitet">
                        <h3>Din anonymitet er beskyttet</h3>
                        <p>Foreleseren vil ikke kunne se hvem som har sendt meldingen. Kun administratorer har tilgang til denne informasjonen ved behov.</p>
                    </aside>

                    <form method="POST" novalidate aria-labelledby="skjema-tittel">
                        <input type="hidden" name="send_melding" value="1">
                        <h3 id="skjema-tittel" class="visually-hidden">Meldingsskjema</h3>
                        
                        <fieldset>
                            <legend class="visually-hidden">Meldingsdetaljer</legend>
                            
                            <div class="skjema-gruppe">
                                <label for="emnekode">Velg emne</label>
                                <select id="emnekode" name="emnekode" required aria-describedby="emne-hjelp">
                                    <option value="">-- Velg et emne --</option>
                                    <?php foreach ($emner as $emne): ?>
                                        <option value="<?php echo $emne['emnekode']; ?>"
                                            <?php echo $valgt_emne == $emne['emnekode'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($emne['emnekode'] . ' - ' . $emne['emnenavn']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="hjelp-tekst" id="emne-hjelp">Velg emnet du ønsker å sende tilbakemelding til.</p>
                            </div>

                            <div class="skjema-gruppe">
                                <label for="innhold">Din melding</label>
                                <textarea id="innhold" name="innhold" placeholder="Skriv din tilbakemelding her..." required aria-describedby="melding-hjelp"></textarea>
                                <p class="hjelp-tekst" id="melding-hjelp">Vær konstruktiv og saklig. Meldingen vil bli sendt anonymt til foreleseren.</p>
                            </div>
                        </fieldset>

                        <button type="submit" class="btn btn-send">Send melding anonymt</button>
                    </form>
                </section>
            </section>
        <?php endif; ?>
    </main>

<?php echo Template::footer(); ?>
