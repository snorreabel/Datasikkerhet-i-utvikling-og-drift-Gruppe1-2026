<?php
/**
 * Felles Dashboard - For alle roller
 * 
 * Håndterer dashboard for:
 * - Admin: Administrasjonspanel med tilgang til brukere, meldinger og rapporter
 * - Foreleser: Se meldinger fra studenter, svare på meldinger
 * - Student: Sende anonyme meldinger, se tidligere sendte meldinger
 * 
 * Rolle bestemmes automatisk fra sesjon
 * 
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/BaseController.php';
require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/db.php';

// Bruk BaseController for felles funksjonalitet
$controller = new BaseController();
$bruker = $controller->bruker;
$rolle = $controller->rolle;
$bruker_id = $bruker['user_id'];
$pdo = $controller->pdo;

// ============================================================================
// ROLLER-BASERT LOGIKK
// ============================================================================

// ----------------------------------------------------------------------------
// ADMIN DASHBOARD
// ----------------------------------------------------------------------------
if ($rolle === 'admin') {
    
    $stats = ['studenter' => 0, 'forelesere' => 0, 'meldinger' => 0, 'rapporter' => 0];
    
    $stmt = $pdo->query("SELECT COUNT(*) as antall FROM bruker WHERE rolle = 'student'");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $stats['studenter'] = $row['antall'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as antall FROM bruker WHERE rolle = 'foreleser'");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $stats['forelesere'] = $row['antall'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as antall FROM melding");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $stats['meldinger'] = $row['antall'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as antall FROM rapport");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $stats['rapporter'] = $row['antall'];
    
    echo Template::header('Admin Dashboard', 'admin', Template::genererNav('admin'));
?>

    <div class="admin-innhold">
            <section class="velkomst" aria-labelledby="velkomst-tittel">
                <h1 id="velkomst-tittel">Velkommen, <?php echo htmlspecialchars($bruker['navn']); ?>!</h1>
                <p>Administrer brukere, meldinger og rapporter fra dette panelet.</p>
            </section>

            <section class="statistikk" aria-label="Statistikk">
                <article class="stat-kort studenter">
                    <h3>Studenter</h3>
                    <p class="tall"><?php echo $stats['studenter']; ?></p>
                </article>
                <article class="stat-kort forelesere">
                    <h3>Forelesere</h3>
                    <p class="tall"><?php echo $stats['forelesere']; ?></p>
                </article>
                <article class="stat-kort meldinger">
                    <h3>Meldinger</h3>
                    <p class="tall"><?php echo $stats['meldinger']; ?></p>
                </article>
                <article class="stat-kort rapporter">
                    <h3>Rapporter</h3>
                    <p class="tall"><?php echo $stats['rapporter']; ?></p>
                </article>
            </section>

            <section class="hurtighandlinger" aria-labelledby="hurtighandlinger-tittel">
                <h2 id="hurtighandlinger-tittel">Hurtighandlinger</h2>
                <nav class="handlinger-grid" aria-label="Hurtighandlinger">
                    <a href="../admin/brukere.php" class="handling-kort">
                        <h3>Administrer brukere</h3>
                        <p>Se, rediger eller slett student- og ansattbrukere</p>
                    </a>
                    <a href="../shared/meldinger.php" class="handling-kort">
                        <h3>Administrer meldinger</h3>
                        <p>Se og slett meldinger og tilhørende svar</p>
                    </a>
                    <a href="../shared/rapporter.php" class="handling-kort">
                        <h3>Rapporterte meldinger</h3>
                        <p>Gjennomgå meldinger som er rapportert som upassende</p>
                    </a>
                    <a href="../admin/brukere.php?tab=sok" class="handling-kort">
                        <h3>Finn avsender</h3>
                        <p>Finn ut hvem som har sendt en anonym melding</p>
                    </a>
                </nav>
            </section>
        </div>

<?php
// ----------------------------------------------------------------------------
// FORELESER DASHBOARD
// ----------------------------------------------------------------------------
} elseif ($rolle === 'foreleser') {
    
    // Hent foreleserens emne(r)
    $emner = [];
    $res = $pdo->query("SELECT * FROM emne WHERE bruker_user_id = '$bruker_id'");
    if ($res) {
        foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $emner[] = $row;
        }
    }
    
    // Hent statistikk
    $stats = [
        'totalt_meldinger' => 0,
        'ubesvarte' => 0,
        'besvarte' => 0
    ];
    
    if (!empty($emner)) {
        $emnekoder = implode(',', array_map(function($e) { return "'" . $e['emnekode'] . "'"; }, $emner));
    
        $res = $pdo->query("SELECT COUNT(*) as antall FROM melding WHERE emne_emnekode IN ($emnekoder)");
        if ($res && $row = $res->fetch(PDO::FETCH_ASSOC)) $stats['totalt_meldinger'] = $row['antall'];
    
        // Merk: Vi har ingen svar-kolonne, så alle meldinger regnes som ubesvarte
        $stats['ubesvarte'] = $stats['totalt_meldinger'];
        $stats['besvarte'] = 0;
    }
    
    // Hent siste meldinger
    $ubesvarte_meldinger = [];
    if (!empty($emner)) {
        $emnekoder = implode(',', array_map(function($e) { return "'" . $e['emnekode'] . "'"; }, $emner));
        $sql = "SELECT m.*, e.emnekode, e.emnenavn
                FROM melding m
                JOIN emne e ON m.emne_emnekode = e.emnekode
                WHERE m.emne_emnekode IN ($emnekoder)
                ORDER BY m.tidspunkt DESC
                LIMIT 5";
        $res = $pdo->query($sql);
        if ($res) {
            foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ubesvarte_meldinger[] = $row;
            }
        }
    }
    
    echo Template::header('Dashboard - Foreleser', 'foreleser', Template::genererNav('foreleser'));
?>

        <section class="velkomst" aria-labelledby="velkomst-tittel">
            <h2 id="velkomst-tittel">Velkommen, <?php echo htmlspecialchars($bruker['navn']); ?>!</h2>
            <p>Her kan du se og svare på anonyme meldinger fra studenter.</p>
        </section>

            <section class="statistikk" aria-label="Meldingsstatistikk">
                <article class="stat-kort">
                    <h3>Totalt meldinger</h3>
                    <p class="tall"><?php echo $stats['totalt_meldinger']; ?></p>
                </article>
                <article class="stat-kort ubesvart">
                    <h3>Ubesvarte</h3>
                    <p class="tall"><?php echo $stats['ubesvarte']; ?></p>
                </article>
                <article class="stat-kort besvart">
                    <h3>Besvarte</h3>
                    <p class="tall"><?php echo $stats['besvarte']; ?></p>
                </article>
            </section>

            <section class="seksjon" aria-labelledby="emner-seksjon-tittel">
                <header class="seksjon-header">
                    <h2 id="emner-seksjon-tittel">Dine emner</h2>
                    <a href="../shared/emner.php" class="btn btn-primary">Opprett nytt emne</a>
                </header>
                <div class="seksjon-innhold">
                    <?php if (empty($emner)): ?>
                        <p class="ingen-innhold" role="status">Du har ikke registrert noen emner ennå.</p>
                    <?php else: ?>
                        <?php foreach ($emner as $emne): ?>
                            <article class="emne-info-boks">
                                <div class="emne-detaljer">
                                    <span class="emnekode"><?php echo htmlspecialchars($emne['emnekode']); ?></span>
                                    <h3><?php echo htmlspecialchars($emne['emnenavn']); ?></h3>
                                    <p>PIN-kode: <strong><?php echo htmlspecialchars($emne['pin_kode']); ?></strong></p>
                                </div>
                                <div class="emne-handlinger" style="display: flex; gap: 1rem; align-items: center;">
                                    <a href="../shared/vis_emne.php?emnekode=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-secondary">Åpne emnesiden</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="seksjon" aria-labelledby="ubesvarte-seksjon-tittel">
                <header class="seksjon-header">
                    <h2 id="ubesvarte-seksjon-tittel">Siste ubesvarte meldinger</h2>
                    <a href="../shared/meldinger.php" class="btn btn-secondary">Se alle</a>
                </header>
                <div class="seksjon-innhold">
                    <?php if (empty($ubesvarte_meldinger)): ?>
                        <p class="ingen-innhold" role="status">Ingen ubesvarte meldinger.</p>
                    <?php else: ?>
                        <div class="meldinger-liste" role="list">
                            <?php foreach ($ubesvarte_meldinger as $melding): ?>
                                <article class="melding-kort" role="listitem">
                                    <p class="melding-tekst"><?php echo htmlspecialchars(mb_substr($melding['innhold'], 0, 150)); ?>...</p>
                                    <footer class="melding-meta">
                                        <time class="tidspunkt" datetime="<?php echo $melding['tidspunkt']; ?>"><?php echo date('d.m.Y H:i', strtotime($melding['tidspunkt'])); ?></time>
                                        <a href="../foreleser/svar.php?id=<?php echo $melding['melding_id']; ?>" class="btn btn-success btn-small" aria-label="Svar på melding fra <?php echo date('d.m.Y', strtotime($melding['tidspunkt'])); ?>">Svar</a>
                                    </footer>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

<?php
// ----------------------------------------------------------------------------
// STUDENT DASHBOARD
// ----------------------------------------------------------------------------
} else {
    
    // Hent statistikk for studenten
    $stats = [
        'sendte_meldinger' => 0,
        'mottatte_svar' => 0
    ];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as antall FROM melding WHERE bruker_user_id = ?");
    $stmt->execute([$bruker_id]);
    if ($row = $stmt->fetch()) {
        $stats['sendte_meldinger'] = $row['antall'];
    }
    
    // Hent antall svar studenten har mottatt
    $stmt = $pdo->prepare("SELECT COUNT(*) as antall FROM melding m 
                           LEFT JOIN kommentar k ON k.melding_id = m.melding_id 
                           WHERE m.bruker_user_id = ? AND k.innhold IS NOT NULL");
    $stmt->execute([$bruker_id]);
    if ($row = $stmt->fetch()) {
        $stats['mottatte_svar'] = $row['antall'];
    }
    
    // Hent tilgjengelige emner - kun de som studenten er registrert i
    $emner = [];
    $stmt = $pdo->prepare("SELECT e.* FROM emne e JOIN bruker_has_emne b ON e.emnekode = b.emne_emnekode WHERE b.bruker_user_id = ? ORDER BY e.emnekode");
    $stmt->execute([$bruker_id]);
    $emner = $stmt->fetchAll();
    
    // Hent siste meldinger med svar
    $siste_svar = [];
    $stmt = $pdo->prepare("SELECT m.*, e.emnekode, e.emnenavn, k.innhold as svar
                           FROM melding m
                           JOIN emne e ON m.emne_emnekode = e.emnekode
                           LEFT JOIN kommentar k ON k.melding_id = m.melding_id
                           WHERE m.bruker_user_id = ? AND k.innhold IS NOT NULL
                           ORDER BY k.tidspunkt DESC
                           LIMIT 3");
    $stmt->execute([$bruker_id]);
    $siste_svar = $stmt->fetchAll();
    
    echo Template::header('Dashboard - Student', 'student', Template::genererNav('student'));
?>

        <section class="velkomst" aria-labelledby="velkomst-tittel">
            <h2 id="velkomst-tittel">Velkommen, <?php echo htmlspecialchars($bruker['navn']); ?>!</h2>
            <p>Send anonyme meldinger til forelesere og følg med på svar.</p>
        </section>

            <section class="statistikk" aria-label="Din statistikk">
                <article class="stat-kort">
                    <h3>Sendte meldinger</h3>
                    <p class="tall"><?php echo $stats['sendte_meldinger']; ?></p>
                </article>
                <article class="stat-kort">
                    <h3>Mottatte svar eller kommentarer</h3>
                    <p class="tall"><?php echo $stats['mottatte_svar']; ?></p>
                </article>
            </section>

            <section class="seksjon" aria-labelledby="emne-seksjon-tittel">
                <header class="seksjon-header">
                    <h2 id="emne-seksjon-tittel">Send melding til et emne</h2>
                    <a href="../shared/student_meldinger.php?tab=send" class="btn btn-primary">Ny melding</a>
                </header>
                <div class="seksjon-innhold">
                    <?php if (empty($emner)): ?>
                        <p class="ingen-innhold" role="status">Ingen emner tilgjengelig.</p>
                    <?php else: ?>
                        <div class="emne-grid" role="list">
                            <?php foreach ($emner as $emne): ?>
                                <article class="emne-kort" role="listitem">
                                    <span class="emnekode"><?php echo htmlspecialchars($emne['emnekode']); ?></span>
                                    <h3><?php echo htmlspecialchars($emne['emnenavn']); ?></h3>
                                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                        <a href="../shared/vis_emne.php?emnekode=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-secondary btn-small">Åpne emnesiden</a>
                                        <a href="../shared/student_meldinger.php?tab=send&emne=<?php echo urlencode($emne['emnekode']); ?>" class="btn btn-primary btn-small">Send melding</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="seksjon" aria-labelledby="svar-seksjon-tittel">
                <header class="seksjon-header">
                    <h2 id="svar-seksjon-tittel">Siste svar fra forelesere</h2>
                    <a href="../shared/student_meldinger.php?tab=mine" class="btn btn-secondary">Se alle</a>
                </header>
                <div class="seksjon-innhold">
                    <?php if (empty($siste_svar)): ?>
                        <p class="ingen-innhold" role="status">Du har ikke mottatt noen svar eller kommentarer ennå.</p>
                    <?php else: ?>
                        <div class="svar-liste">
                            <?php foreach ($siste_svar as $melding): ?>
                                <article class="svar-kort">
                                    <p class="emne-info"><?php echo htmlspecialchars($melding['emnekode']); ?> - <?php echo htmlspecialchars($melding['emnenavn']); ?></p>
                                    <p class="melding-utdrag">"<?php echo htmlspecialchars(mb_substr($melding['innhold'], 0, 100)); ?>..."</p>
                                    <p class="svar-tekst"><?php echo htmlspecialchars($melding['svar']); ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

<?php
}

echo Template::footer();
?>
