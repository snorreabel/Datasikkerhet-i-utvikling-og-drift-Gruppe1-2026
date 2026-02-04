<?php
/**
 * Admin - Brukerhåndtering
 *
 * Konsolidert side for admin:
 * - Tab 1: Brukere (se, rediger, slett brukere)
 * - Tab 2: Søk avsender (de-anonymiser meldinger)
 *
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/BaseController.php';
require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/db.php';

// Bruk BaseController - tillat kun admin
$controller = new BaseController(['admin']);
$admin = $controller->bruker;
$pdo = $controller->pdo;

$melding = '';
$feil = '';
$filter = $_GET['filter'] ?? 'alle';
$active_tab = $_GET['tab'] ?? 'brukere';

// ====================================================================
// SØK AVSENDER (Tab 2)
// ====================================================================

$resultat = null;
$sokt = false;

if ($active_tab === 'sok' && ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['melding_id']))) {
    $melding_id = $_POST['melding_id'] ?? $_GET['melding_id'] ?? '';
    $sokt = true;
    
    if (!empty($melding_id) && is_numeric($melding_id)) {
        $sql = "SELECT m.*, b.navn, b.epost, e.emnekode, e.emnenavn,
                       si.studieretning, si.kull as studiekull
                FROM melding m
                JOIN bruker b ON m.bruker_user_id = b.user_id
                LEFT JOIN emne e ON m.emne_emnekode = e.emnekode
                LEFT JOIN student_info si ON b.user_id = si.bruker_user_id
                WHERE m.melding_id = $melding_id";
        $query = $pdo->query($sql);
        
        if ($query && $query->rowCount() > 0) {
            $resultat = $query->fetch(PDO::FETCH_ASSOC);
        }
    }
}

// ====================================================================
// BRUKERHÅNDTERING (Tab 1)
// ====================================================================

// Håndter sletting av bruker
if (isset($_GET['slett']) && is_numeric($_GET['slett'])) {
    $bruker_id = $_GET['slett'];

    // Ikke tillat sletting av seg selv
    if ($bruker_id == $admin['user_id']) {
        $feil = 'Du kan ikke slette din egen bruker.';
    } else {
        // Slett først relaterte meldinger
        $stmt = $pdo->prepare("DELETE FROM melding WHERE bruker_user_id = ?");
        $stmt->execute([$bruker_id]);

        // Slett student_info hvis finnes
        $stmt = $pdo->prepare("DELETE FROM student_info WHERE bruker_user_id = ?");
        $stmt->execute([$bruker_id]);

        // Slett brukeren
        $stmt = $pdo->prepare("DELETE FROM bruker WHERE user_id = ?");
        if ($stmt->execute([$bruker_id])) {
            $melding = 'Brukeren ble slettet.';
        } else {
            $feil = 'Kunne ikke slette brukeren.';
        }
    }
}

// Håndter oppdatering av bruker
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oppdater_bruker'])) {
    $bruker_id = $_POST['bruker_id'] ?? '';
    $navn = trim($_POST['navn'] ?? '');
    $epost = trim($_POST['epost'] ?? '');
    $rolle = $_POST['rolle'] ?? '';

    if (!empty($bruker_id) && is_numeric($bruker_id) && !empty($navn) && !empty($epost)) {
        $stmt = $pdo->prepare("UPDATE bruker SET navn = ?, epost = ?, rolle = ? WHERE user_id = ?");
        if ($stmt->execute([$navn, $epost, $rolle, $bruker_id])) {
            $melding = 'Brukerinformasjon ble oppdatert.';
        } else {
            $feil = 'Kunne ikke oppdatere brukeren.';
        }
    }
}

// Filtrering
$where = "WHERE rolle != 'admin'";
if ($filter === 'student') $where = "WHERE rolle = 'student'";
elseif ($filter === 'foreleser') $where = "WHERE rolle = 'foreleser'";
elseif ($filter === 'admin') $where = "WHERE rolle = 'admin'";

// Hent brukere
$sql = "SELECT * FROM bruker $where ORDER BY user_id DESC";
$stmt = $pdo->query($sql);
$brukere = $stmt;

// Hent siste meldinger for søk avsender
$siste_meldinger = $pdo->query("SELECT melding_id, LEFT(innhold, 50) as utdrag, tidspunkt FROM melding ORDER BY tidspunkt DESC LIMIT 10");

echo Template::header('Brukere | Admin', 'admin', Template::genererNav('admin'));
?>

<div class="admin-innhold">
        <h2>Brukerhåndtering</h2>

    <?php if ($melding) echo Template::visSuksess($melding); ?>

    <?php if ($feil) echo Template::visFeil($feil); ?>

    <!-- Tab navigasjon -->
    <nav class="tab-navigasjon" role="tablist" style="margin-bottom: 1.5rem; border-bottom: 2px solid #2a2a4a;">
        <a href="?tab=brukere&filter=<?php echo $filter; ?>" class="tab-link <?php echo $active_tab === 'brukere' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'brukere' ? 'true' : 'false'; ?>" style="display: inline-block; padding: 0.75rem 1.5rem; background: <?php echo $active_tab === 'brukere' ? '#3498db' : 'transparent'; ?>; color: white; text-decoration: none; border-radius: 4px 4px 0 0; margin-right: 0.5rem;">Brukere</a>
        <a href="?tab=sok" class="tab-link <?php echo $active_tab === 'sok' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'sok' ? 'true' : 'false'; ?>" style="display: inline-block; padding: 0.75rem 1.5rem; background: <?php echo $active_tab === 'sok' ? '#3498db' : 'transparent'; ?>; color: white; text-decoration: none; border-radius: 4px 4px 0 0;">Søk avsender</a>
    </nav>

    <?php if ($active_tab === 'brukere'): ?>
        <!-- BRUKERE TAB -->
        <section class="tab-innhold" role="tabpanel">
    <nav class="filter" aria-label="Filtrer brukere" style="margin-bottom: 1.5rem;">
        <a href="?tab=brukere&filter=alle" class="<?php echo $filter === 'alle' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; margin-right: 0.5rem; background: <?php echo $filter === 'alle' ? '#3498db' : '#2a2a4a'; ?>; color: white; text-decoration: none; border-radius: 4px;">Alle</a>
        <a href="?tab=brukere&filter=student" class="<?php echo $filter === 'student' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; margin-right: 0.5rem; background: <?php echo $filter === 'student' ? '#3498db' : '#2a2a4a'; ?>; color: white; text-decoration: none; border-radius: 4px;">Studenter</a>
        <a href="?tab=brukere&filter=foreleser" class="<?php echo $filter === 'foreleser' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; margin-right: 0.5rem; background: <?php echo $filter === 'foreleser' ? '#3498db' : '#2a2a4a'; ?>; color: white; text-decoration: none; border-radius: 4px;">Forelesere</a>
        <a href="?tab=brukere&filter=admin" class="<?php echo $filter === 'admin' ? 'active' : ''; ?>" style="padding: 0.5rem 1rem; background: <?php echo $filter === 'admin' ? '#e74c3c' : '#2a2a4a'; ?>; color: white; text-decoration: none; border-radius: 4px;">Admins</a>
    </nav>

    <section aria-labelledby="bruker-tabell-tittel">
        <h3 id="bruker-tabell-tittel" class="visually-hidden">Brukerliste</h3>

        <?php
        $brukere_liste = $brukere ? $brukere->fetchAll(PDO::FETCH_ASSOC) : [];
        if (!empty($brukere_liste)):
        ?>
        <div class="bruker-liste">
            <?php foreach ($brukere_liste as $bruker): ?>
            <article class="bruker-kort" style="background: #1a1a2e; border: 1px solid #2a2a4a; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                <header style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                    <div>
                        <span class="rolle-badge" style="background: <?php echo $bruker['rolle'] === 'admin' ? '#e74c3c' : ($bruker['rolle'] === 'foreleser' ? '#9b59b6' : '#3498db'); ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?php echo ucfirst($bruker['rolle']); ?></span>
                        <span style="color: #aaa; margin-left: 0.5rem;">ID: <?php echo $bruker['user_id']; ?></span>
                    </div>
                    <div class="bruker-handlinger">
                        <button onclick="toggleRedigerBruker(<?php echo $bruker['user_id']; ?>)" class="btn btn-liten" style="background: #3498db; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Rediger</button>
                        <?php if ($bruker['user_id'] != $admin['user_id']): ?>
                        <a href="?slett=<?php echo $bruker['user_id']; ?>&filter=<?php echo $filter; ?>" onclick="return confirm('Er du sikker på at du vil slette denne brukeren? Alle tilknyttede meldinger vil også bli slettet.');" class="btn btn-liten" style="background: #e74c3c; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none;">Slett</a>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="bruker-info" id="info-<?php echo $bruker['user_id']; ?>">
                    <dl style="display: grid; grid-template-columns: auto 1fr; gap: 0.5rem 1rem;">
                        <dt style="color: #aaa;">Navn:</dt>
                        <dd style="color: #eee; margin: 0;"><?php echo htmlspecialchars($bruker['navn']); ?></dd>
                        <dt style="color: #aaa;">E-post:</dt>
                        <dd style="color: #eee; margin: 0;"><?php echo htmlspecialchars($bruker['epost']); ?></dd>
                    </dl>
                </div>

                <form method="POST" id="rediger-<?php echo $bruker['user_id']; ?>" style="display: none; margin-top: 1rem;">
                    <input type="hidden" name="bruker_id" value="<?php echo $bruker['user_id']; ?>">
                    <div style="display: grid; gap: 1rem;">
                        <div>
                            <label for="navn-<?php echo $bruker['user_id']; ?>" style="color: #aaa; display: block; margin-bottom: 0.25rem;">Navn:</label>
                            <input type="text" id="navn-<?php echo $bruker['user_id']; ?>" name="navn" value="<?php echo htmlspecialchars($bruker['navn']); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #2a2a4a; border-radius: 4px; background: #16213e; color: #eee;">
                        </div>
                        <div>
                            <label for="epost-<?php echo $bruker['user_id']; ?>" style="color: #aaa; display: block; margin-bottom: 0.25rem;">E-post:</label>
                            <input type="email" id="epost-<?php echo $bruker['user_id']; ?>" name="epost" value="<?php echo htmlspecialchars($bruker['epost']); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid #2a2a4a; border-radius: 4px; background: #16213e; color: #eee;">
                        </div>
                        <div>
                            <label for="rolle-<?php echo $bruker['user_id']; ?>" style="color: #aaa; display: block; margin-bottom: 0.25rem;">Rolle:</label>
                            <select id="rolle-<?php echo $bruker['user_id']; ?>" name="rolle" style="width: 100%; padding: 0.5rem; border: 1px solid #2a2a4a; border-radius: 4px; background: #16213e; color: #eee;">
                                <option value="student" <?php echo $bruker['rolle'] === 'student' ? 'selected' : ''; ?>>Student</option>
                                <option value="foreleser" <?php echo $bruker['rolle'] === 'foreleser' ? 'selected' : ''; ?>>Foreleser</option>
                                <option value="admin" <?php echo $bruker['rolle'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 1rem;">
                        <button type="submit" name="oppdater_bruker" class="btn" style="background: #27ae60; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Lagre</button>
                        <button type="button" onclick="toggleRedigerBruker(<?php echo $bruker['user_id']; ?>)" class="btn" style="background: #7f8c8d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-left: 0.5rem;">Avbryt</button>
                    </div>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="ingen-brukere" style="text-align: center; color: #aaa; padding: 2rem;">
                <p>Ingen brukere funnet med valgt filter.</p>
            </div>
        <?php endif; ?>
    </section>
        </section>

    <?php else: ?>
        <!-- SØK AVSENDER TAB -->
        <section class="tab-innhold" role="tabpanel">
    <aside class="advarsel" role="alert" aria-live="polite" style="background: #2c3e50; border-left: 4px solid #e74c3c; padding: 1rem; margin-bottom: 1.5rem; border-radius: 4px;">
        <strong>Viktig:</strong> Denne funksjonen de-anonymiserer meldinger. Bruk kun ved alvorlige brudd på retningslinjer.
    </aside>

    <section class="sok-seksjon" aria-labelledby="sok-tittel">
        <h3 id="sok-tittel">Søk etter melding</h3>
        <form method="POST" action="?tab=sok" class="sok-form" role="search" novalidate style="display: flex; gap: 0.5rem; margin-bottom: 2rem;">
            <label for="melding-id" class="visually-hidden">Melding-ID</label>
            <input type="number" id="melding-id" name="melding_id" placeholder="Skriv inn melding-ID" required value="<?php echo htmlspecialchars($_POST['melding_id'] ?? $_GET['melding_id'] ?? ''); ?>" aria-describedby="sok-hjelp" style="flex: 1; padding: 0.75rem; border: 1px solid #2a2a4a; border-radius: 4px; background: #16213e; color: #eee;">
            <span id="sok-hjelp" class="visually-hidden">Skriv inn meldingsnummeret du vil søke etter</span>
            <button type="submit" class="btn btn-primary" style="background: #3498db; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 4px; cursor: pointer;">Søk</button>
        </form>
    </section>

    <?php if ($sokt): ?>
        <?php if ($resultat): ?>
        <section class="resultat" aria-labelledby="resultat-tittel" style="background: #1a1a2e; border: 1px solid #2a2a4a; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <header class="resultat-header">
                <h3 id="resultat-tittel" style="color: #27ae60; margin-top: 0;">Avsenderinformasjon funnet</h3>
            </header>
            <div class="resultat-innhold">
                <dl class="info-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                    <div class="info-felt">
                        <dt style="color: #aaa; font-weight: bold;">Navn</dt>
                        <dd class="verdi" style="color: #eee; margin: 0.25rem 0 0 0;"><?php echo htmlspecialchars($resultat['navn']); ?></dd>
                    </div>
                    <div class="info-felt">
                        <dt style="color: #aaa; font-weight: bold;">E-post</dt>
                        <dd class="verdi" style="color: #eee; margin: 0.25rem 0 0 0;"><?php echo htmlspecialchars($resultat['epost']); ?></dd>
                    </div>
                    <div class="info-felt">
                        <dt style="color: #aaa; font-weight: bold;">Studieretning</dt>
                        <dd class="verdi" style="color: #eee; margin: 0.25rem 0 0 0;"><?php echo htmlspecialchars($resultat['studieretning'] ?? 'Ikke oppgitt'); ?></dd>
                    </div>
                    <div class="info-felt">
                        <dt style="color: #aaa; font-weight: bold;">Studiekull</dt>
                        <dd class="verdi" style="color: #eee; margin: 0.25rem 0 0 0;"><?php echo htmlspecialchars($resultat['studiekull'] ?? 'Ikke oppgitt'); ?></dd>
                    </div>
                    <div class="info-felt">
                        <dt style="color: #aaa; font-weight: bold;">Emne</dt>
                        <dd class="verdi" style="color: #eee; margin: 0.25rem 0 0 0;"><?php echo htmlspecialchars($resultat['emnekode'] . ' - ' . $resultat['emnenavn']); ?></dd>
                    </div>
                    <div class="info-felt">
                        <dt style="color: #aaa; font-weight: bold;">Sendt</dt>
                        <dd class="verdi" style="color: #eee; margin: 0.25rem 0 0 0;"><time datetime="<?php echo $resultat['tidspunkt']; ?>"><?php echo date('d.m.Y H:i', strtotime($resultat['tidspunkt'])); ?></time></dd>
                    </div>
                </dl>

                <article class="melding-boks" style="background: #2c3e50; padding: 1rem; border-radius: 4px;">
                    <h4 style="color: #ecf0f1; margin-top: 0;">Meldingsinnhold:</h4>
                    <p style="color: #ecf0f1;"><?php echo nl2br(htmlspecialchars($resultat['innhold'])); ?></p>
                </article>
            </div>
        </section>
        <?php else: ?>
        <div class="ingen-resultat" role="status" style="background: #2c3e50; padding: 1.5rem; border-radius: 8px; text-align: center; color: #e74c3c;">
            <p>Ingen melding funnet med ID: <?php echo htmlspecialchars($_POST['melding_id'] ?? $_GET['melding_id'] ?? ''); ?></p>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="siste-meldinger" aria-labelledby="siste-meldinger-tittel" style="margin-top: 2rem;">
        <h3 id="siste-meldinger-tittel">Siste meldinger</h3>
        <ul class="siste-liste" style="list-style: none; padding: 0;">
            <?php 
            $siste_liste = $siste_meldinger ? $siste_meldinger->fetchAll(PDO::FETCH_ASSOC) : [];
            if (!empty($siste_liste)): 
            ?>
                <?php foreach ($siste_liste as $m): ?>
                <li style="background: #1a1a2e; border: 1px solid #2a2a4a; padding: 1rem; border-radius: 4px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span class="id" aria-label="Melding nummer" style="background: #3498db; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; margin-right: 0.5rem;">#<?php echo $m['melding_id']; ?></span>
                        <span class="utdrag" style="color: #ccc;"><?php echo htmlspecialchars($m['utdrag']); ?>...</span>
                    </div>
                    <a href="?tab=sok&melding_id=<?php echo $m['melding_id']; ?>" aria-label="Vis avsender for melding <?php echo $m['melding_id']; ?>" style="background: #2a2a4a; color: white; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; white-space: nowrap;">Vis avsender</a>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li style="color: #aaa; text-align: center; padding: 1rem;"><span class="utdrag">Ingen meldinger funnet</span></li>
            <?php endif; ?>
        </ul>
    </section>
        </section>
    <?php endif; ?>
</div>

<script>
function toggleRedigerBruker(brukerId) {
    var form = document.getElementById('rediger-' + brukerId);
    var info = document.getElementById('info-' + brukerId);

    if (form.style.display === 'none') {
        form.style.display = 'block';
        info.style.display = 'none';
    } else {
        form.style.display = 'none';
        info.style.display = 'block';
    }
}
</script>

<?php echo Template::footer(); ?>
