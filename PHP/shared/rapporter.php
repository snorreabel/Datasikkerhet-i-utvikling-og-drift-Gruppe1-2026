<?php
/**
 * Shared - Rapporter
 *
 * Rolle-basert rapport-håndtering:
 * - Foreleser: Se rapporter for egne emner
 * - Admin: Se alle rapporter, håndtere/slette
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

$suksessmelding = '';
$feilmelding = '';

// ====================================================================
// ADMIN: Slett rapport
// ====================================================================

if ($rolle === 'admin' && isset($_GET['slett']) && is_numeric($_GET['slett'])) {
    $rapport_id = $_GET['slett'];
    $stmt = $pdo->prepare("DELETE FROM rapport WHERE rapport_id = ?");
    if ($stmt->execute([$rapport_id])) {
        $suksessmelding = 'Rapporten ble slettet.';
    } else {
        $feilmelding = 'Kunne ikke slette rapporten.';
    }
}

// ====================================================================
// HENT RAPPORTER
// ====================================================================

$rapporter = [];

if ($rolle === 'foreleser') {
    // Foreleser: Kun rapporter for egne emner
    $bruker_id = $bruker['user_id'];
    
    $sql = "SELECT r.*, m.innhold as melding_innhold, m.tidspunkt as melding_tid,
                   e.emnekode, e.emnenavn, b.navn as student_navn, m.bruker_user_id as student_id
            FROM rapport r
            JOIN melding m ON r.melding_melding_id = m.melding_id
            JOIN emne e ON m.emne_emnekode = e.emnekode
            LEFT JOIN bruker b ON m.bruker_user_id = b.user_id
            WHERE e.bruker_user_id = ?
            ORDER BY r.tidspunkt DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bruker_id]);
    $rapporter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} elseif ($rolle === 'admin') {
    // Admin: Alle rapporter
    $sql = "SELECT r.*, m.innhold as melding_innhold, m.tidspunkt as melding_tid,
                   e.emnekode, e.emnenavn, b.navn as student_navn, m.bruker_user_id as student_id
            FROM rapport r
            JOIN melding m ON r.melding_melding_id = m.melding_id
            LEFT JOIN emne e ON m.emne_emnekode = e.emnekode
            LEFT JOIN bruker b ON m.bruker_user_id = b.user_id
            ORDER BY r.tidspunkt DESC";
    $rapporter = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// ====================================================================
// NAVIGASJON
// ====================================================================

$side_tittel = $rolle === 'foreleser' ? 'Rapporter - Foreleser' : 'Rapporter | Admin';
echo Template::header($side_tittel, $rolle, Template::genererNav($rolle));
?>

<?php if ($rolle === 'admin'): ?>
    <div class="admin-innhold">
        <h2>Rapporterte meldinger</h2>
        
        <?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

        <?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

        <p style="color: #7f8c8d; margin-bottom: 1rem;">Handlinger (godkjenn/markere) kommer i neste oppdatering</p>

        <section aria-label="Liste over rapporterte meldinger">
            <?php if (empty($rapporter)): ?>
                <div class="ingen-rapporter" role="status">
                    <p style="color: #aaa;">Ingen rapporterte meldinger</p>
                </div>
            <?php else: ?>
                <?php foreach ($rapporter as $r): ?>
                    <article class="rapport-kort" aria-labelledby="rapport-<?php echo $r['rapport_id']; ?>" style="background: #1a1a2e; border: 1px solid #2a2a4a; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                        <header class="rapport-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                            <div class="rapport-info">
                                <h3 id="rapport-<?php echo $r['rapport_id']; ?>" style="color: #e74c3c; margin: 0;">Rapport #<?php echo $r['rapport_id']; ?></h3>
                                <time class="dato" datetime="<?php echo $r['tidspunkt']; ?>" style="color: #aaa; font-size: 0.9rem;">Rapportert: <?php echo date('d.m.Y H:i', strtotime($r['tidspunkt'])); ?></time>
                            </div>
                            <a href="?slett=<?php echo $r['rapport_id']; ?>" onclick="return confirm('Er du sikker på at du vil slette denne rapporten?');" class="btn btn-liten" style="background: #e74c3c; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">Slett</a>
                        </header>

                        <?php if (!empty($r['beskrivelse'])): ?>
                        <aside class="rapport-grunn" aria-label="Begrunnelse for rapport" style="background: #2c3e50; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                            <h4 style="color: #ecf0f1; margin-top: 0;">Begrunnelse:</h4>
                            <p style="color: #ecf0f1; margin: 0;"><?php echo htmlspecialchars($r['beskrivelse']); ?></p>
                        </aside>
                        <?php endif; ?>

                        <div class="melding-innhold">
                            <div class="melding-meta" style="margin-bottom: 0.5rem;">
                                <span class="emne-badge" style="background: #3498db; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($r['emnekode'] ?? 'Ukjent'); ?></span>
                                <span class="avsender" style="color: #ccc; margin-left: 0.5rem;"><?php echo htmlspecialchars($r['student_navn'] ?? 'Ukjent'); ?> (ID: <?php echo $r['student_id']; ?>)</span>
                                <time class="dato" datetime="<?php echo $r['melding_tid']; ?>" style="color: #aaa; margin-left: 0.5rem;"><?php echo date('d.m.Y H:i', strtotime($r['melding_tid'])); ?></time>
                            </div>
                            <p style="color: #eee;"><?php echo nl2br(htmlspecialchars($r['melding_innhold'])); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

<?php else: /* FORELESER VIEW */ ?>

<header class="side-header">
    <h2>Rapporterte meldinger</h2>
    <p>Meldinger fra dine emner som er rapportert som upassende</p>
</header>

<?php if ($suksessmelding) echo Template::visSuksess($suksessmelding); ?>

<?php if ($feilmelding) echo Template::visFeil($feilmelding); ?>

<section aria-label="Liste over rapporterte meldinger">
    <?php if (empty($rapporter)): ?>
        <div class="ingen-rapporter" role="status">
            <p>Ingen rapporterte meldinger i dine emner</p>
        </div>
    <?php else: ?>
        <?php foreach ($rapporter as $r): ?>
            <article class="rapport-kort" aria-labelledby="rapport-<?php echo $r['rapport_id']; ?>">
                <header class="rapport-header">
                    <div class="rapport-info">
                        <h3 id="rapport-<?php echo $r['rapport_id']; ?>">Rapport #<?php echo $r['rapport_id']; ?></h3>
                        <time class="dato" datetime="<?php echo $r['tidspunkt']; ?>">Rapportert: <?php echo date('d.m.Y H:i', strtotime($r['tidspunkt'])); ?></time>
                    </div>
                </header>

                <?php if (!empty($r['beskrivelse'])): ?>
                <aside class="rapport-grunn" aria-label="Begrunnelse for rapport">
                    <h4>Begrunnelse:</h4>
                    <p><?php echo htmlspecialchars($r['beskrivelse']); ?></p>
                </aside>
                <?php endif; ?>

                <div class="melding-innhold">
                    <div class="melding-meta">
                        <span class="emne-badge"><?php echo htmlspecialchars($r['emnekode'] ?? 'Ukjent'); ?></span>
                        <span class="avsender">Anonym student</span>
                        <time class="dato" datetime="<?php echo $r['melding_tid']; ?>"><?php echo date('d.m.Y H:i', strtotime($r['melding_tid'])); ?></time>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($r['melding_innhold'])); ?></p>
                </div>

                <div class="rapport-handlinger" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                    <p style="color: #7f8c8d; font-size: 0.9rem;">💡 Kontakt admin for å slette eller ta ytterligere handling på denne meldingen</p>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php endif; ?>

<?php echo Template::footer(); ?>
