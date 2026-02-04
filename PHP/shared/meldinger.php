<?php
/**
 * Shared - Meldinger
 *
 * Rolle-basert meldingsvisning:
 * - Foreleser: Se meldinger fra egne emner, svare på meldinger
 * - Admin: Se alle meldinger, redigere og slette
 *
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/BaseController.php';
require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';
require_once __DIR__ . '/../utility/db.php';

// Bruk BaseController - tillat kun foreleser og admin
$controller = new BaseController(['foreleser', 'admin']);
$bruker = $controller->bruker;
$rolle = $controller->rolle;
$pdo = $controller->pdo;

// ====================================================================
// ADMIN-FUNKSJONALITET: Sletting og redigering
// ====================================================================

$melding_slettet = false;
$melding_oppdatert = false;
$feilmelding = '';

if ($rolle === 'admin') {
    // Håndter sletting
    if (isset($_GET['slett']) && is_numeric($_GET['slett'])) {
        $melding_id = $_GET['slett'];
        $stmt = $pdo->prepare("DELETE FROM melding WHERE melding_id = ?");
        if ($stmt->execute([$melding_id])) {
            $melding_slettet = true;
        } else {
            $feilmelding = 'Kunne ikke slette meldingen';
        }
    }

    // Håndter oppdatering
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['oppdater_melding'])) {
        $melding_id = $_POST['melding_id'] ?? '';
        $nytt_innhold = $_POST['innhold'] ?? '';

        if (!empty($melding_id) && is_numeric($melding_id) && !empty($nytt_innhold)) {
            $stmt = $pdo->prepare("UPDATE melding SET innhold = ? WHERE melding_id = ?");
            if ($stmt->execute([$nytt_innhold, $melding_id])) {
                $melding_oppdatert = true;
            } else {
                $feilmelding = 'Kunne ikke oppdatere meldingen';
            }
        }
    }
}

// ====================================================================
// HENT MELDINGER
// ====================================================================

$meldinger = [];
$filter = $_GET['filter'] ?? 'alle';

if ($rolle === 'foreleser') {
    // Foreleser: Kun egne emner
    $bruker_id = $bruker['user_id'];
    
    $emner = [];
    $res = $pdo->query("SELECT * FROM emne WHERE bruker_user_id = '$bruker_id'");
    if ($res) {
        foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $emner[] = $row;
        }
    }

    if (!empty($emner)) {
        $emnekoder = implode(',', array_map(function($e) { return "'" . $e['emnekode'] . "'"; }, $emner));

        $sql = "SELECT m.*, e.emnekode, e.emnenavn, b.navn as student_navn, k.innhold as svar
                FROM melding m
                JOIN emne e ON m.emne_emnekode = e.emnekode
                LEFT JOIN bruker b ON m.bruker_user_id = b.user_id
                LEFT JOIN kommentar k ON k.melding_id = m.melding_id
                WHERE m.emne_emnekode IN ($emnekoder)
                ORDER BY m.tidspunkt DESC";

        $res = $pdo->query($sql);
        if ($res) {
            foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $meldinger[] = $row;
            }
        }
    }
} elseif ($rolle === 'admin') {
    // Admin: Alle meldinger
    $sql = "SELECT m.*, e.emnekode, e.emnenavn, b.navn as student_navn
            FROM melding m
            LEFT JOIN emne e ON m.emne_emnekode = e.emnekode
            LEFT JOIN bruker b ON m.bruker_user_id = b.user_id
            ORDER BY m.tidspunkt DESC";
    $res = $pdo->query($sql);
    
    if ($res) {
        foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $meldinger[] = $row;
        }
    }
}

// Tell statistikk (kun for foreleser)
$antall = ['alle' => count($meldinger), 'ubesvarte' => 0, 'besvarte' => 0];
if ($rolle === 'foreleser') {
    foreach ($meldinger as $m) {
        if (!empty($m['svar'])) {
            $antall['besvarte']++;
        } else {
            $antall['ubesvarte']++;
        }
    }
}

// ====================================================================
// NAVIGASJON
// ====================================================================

$side_tittel = $rolle === 'foreleser' ? 'Meldinger - Foreleser' : 'Meldinger | Admin';
echo Template::header($side_tittel, $rolle, Template::genererNav($rolle));
?>

<?php if ($rolle === 'admin'): ?>
    <div class="admin-innhold">
        <h2>Meldingshåndtering</h2>

        <?php if ($melding_slettet): ?>
            <div class="suksess-melding" style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                Meldingen ble slettet.
            </div>
        <?php endif; ?>

        <?php if ($melding_oppdatert): ?>
            <div class="suksess-melding" style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                Meldingen ble oppdatert.
            </div>
        <?php endif; ?>

        <?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

        <section class="meldinger-liste" aria-label="Liste over meldinger">
            <?php if (empty($meldinger)): ?>
                <div class="ingen-meldinger" role="status">
                    <p style="color: #aaa;">Ingen meldinger funnet</p>
                </div>
            <?php else: ?>
                <?php foreach ($meldinger as $m): ?>
                    <article class="melding-kort" aria-labelledby="melding-<?php echo $m['melding_id']; ?>" style="background: #1a1a2e; border: 1px solid #2a2a4a; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                        <header class="melding-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div class="melding-meta">
                                <span class="emne-badge" style="background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($m['emnekode'] ?? 'Ukjent'); ?></span>
                                <time class="dato" datetime="<?php echo $m['tidspunkt']; ?>" style="color: #aaa; margin-left: 0.5rem;"><?php echo date('d.m.Y H:i', strtotime($m['tidspunkt'])); ?></time>
                                <span class="avsender" style="color: #ccc; display: block; margin-top: 0.5rem;"><?php echo htmlspecialchars($m['student_navn'] ?? 'Ukjent'); ?> (ID: <?php echo $m['bruker_user_id']; ?>)</span>
                            </div>
                            <div class="melding-handlinger">
                                <button onclick="toggleRediger(<?php echo $m['melding_id']; ?>)" class="btn btn-liten" style="background: #3498db; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-right: 0.5rem;">Rediger</button>
                                <a href="?slett=<?php echo $m['melding_id']; ?>" onclick="return confirm('Er du sikker på at du vil slette denne meldingen?');" class="btn btn-liten" style="background: #e74c3c; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none;">Slett</a>
                            </div>
                        </header>

                        <div class="melding-innhold" id="innhold-<?php echo $m['melding_id']; ?>">
                            <p id="melding-<?php echo $m['melding_id']; ?>" style="color: #eee;"><?php echo nl2br(htmlspecialchars($m['innhold'])); ?></p>
                        </div>

                        <form method="POST" id="rediger-<?php echo $m['melding_id']; ?>" style="display: none; margin-top: 1rem;">
                            <input type="hidden" name="melding_id" value="<?php echo $m['melding_id']; ?>">
                            <textarea name="innhold" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #2a2a4a; border-radius: 4px; background: #16213e; color: #eee; resize: vertical;"><?php echo htmlspecialchars($m['innhold']); ?></textarea>
                            <div style="margin-top: 0.5rem;">
                                <button type="submit" name="oppdater_melding" class="btn" style="background: #27ae60; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Lagre</button>
                                <button type="button" onclick="toggleRediger(<?php echo $m['melding_id']; ?>)" class="btn" style="background: #7f8c8d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; margin-left: 0.5rem;">Avbryt</button>
                            </div>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

<script>
function toggleRediger(meldingId) {
    var form = document.getElementById('rediger-' + meldingId);
    var innhold = document.getElementById('innhold-' + meldingId);

    if (form.style.display === 'none') {
        form.style.display = 'block';
        innhold.style.display = 'none';
    } else {
        form.style.display = 'none';
        innhold.style.display = 'block';
    }
}
</script>

<?php else: /* FORELESER VIEW */ ?>

    <main class="hoved-innhold">
        <header class="side-header">
            <h2>Meldinger fra studenter</h2>
            <p>Les og svar på anonyme meldinger fra studentene dine.</p>
        </header>

<nav class="filter-knapper" aria-label="Filtrer meldinger">
    <a href="?filter=alle" class="filter-knapp <?php echo $filter === 'alle' ? 'aktiv' : ''; ?>" <?php echo $filter === 'alle' ? 'aria-current="page"' : ''; ?>>
        Alle<span class="antall"><?php echo $antall['alle']; ?></span>
    </a>
    <a href="?filter=ubesvarte" class="filter-knapp <?php echo $filter === 'ubesvarte' ? 'aktiv' : ''; ?>" <?php echo $filter === 'ubesvarte' ? 'aria-current="page"' : ''; ?>>
        Ubesvarte<span class="antall"><?php echo $antall['ubesvarte']; ?></span>
    </a>
    <a href="?filter=besvarte" class="filter-knapp <?php echo $filter === 'besvarte' ? 'aktiv' : ''; ?>" <?php echo $filter === 'besvarte' ? 'aria-current="page"' : ''; ?>>
        Besvarte<span class="antall"><?php echo $antall['besvarte']; ?></span>
    </a>
</nav>

<section class="meldinger-liste" aria-label="Liste over meldinger">
    <?php if (empty($meldinger)): ?>
        <div class="ingen-meldinger" role="status">
            <p>Ingen meldinger å vise.</p>
        </div>
    <?php else: ?>
        <?php 
        // Filtrer basert på valgt filter
        $filtrerte_meldinger = $meldinger;
        if ($filter === 'ubesvarte') {
            $filtrerte_meldinger = array_filter($meldinger, function($m) { return empty($m['svar']); });
        } elseif ($filter === 'besvarte') {
            $filtrerte_meldinger = array_filter($meldinger, function($m) { return !empty($m['svar']); });
        }
        
        foreach ($filtrerte_meldinger as $melding): 
        ?>
            <article class="melding-kort" aria-labelledby="melding-<?php echo $melding['melding_id']; ?>">
                <header class="melding-header">
                    <span class="emne-badge" id="melding-<?php echo $melding['melding_id']; ?>"><?php echo htmlspecialchars($melding['emnekode']); ?> - <?php echo htmlspecialchars($melding['emnenavn']); ?></span>
                    <time class="melding-dato" datetime="<?php echo $melding['tidspunkt']; ?>"><?php echo date('d.m.Y H:i', strtotime($melding['tidspunkt'])); ?></time>
                </header>

                <div class="melding-innhold">
                    <p><?php echo nl2br(htmlspecialchars($melding['innhold'])); ?></p>
                    <?php if (!$melding['svar']): ?>
                        <nav class="melding-handlinger">
                            <a href="../foreleser/svar.php?id=<?php echo $melding['melding_id']; ?>" class="btn btn-success">Svar på melding</a>
                        </nav>
                    <?php endif; ?>
                </div>

                <?php if ($melding['svar']): ?>
                    <aside class="melding-svar" aria-label="Ditt svar">
                        <p class="svar-label">Ditt svar:</p>
                        <p><?php echo nl2br(htmlspecialchars($melding['svar'])); ?></p>
                    </aside>
                <?php else: ?>
                    <div class="ubesvart-indikator" role="status">
                        <span>Denne meldingen venter på svar</span>
                        <a href="../foreleser/svar.php?id=<?php echo $melding['melding_id']; ?>" class="btn btn-success btn-small">Svar nå</a>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
    </main>

<?php endif; ?>

<?php echo Template::footer(); ?>
