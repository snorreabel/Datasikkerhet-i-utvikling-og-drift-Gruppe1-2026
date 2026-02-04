<?php
/**
 * Glemt passord - Steg 1
 * 
 * Genererer en tilbakestillingstoken og lar brukeren velge sitt eget passord.
 * 
 * Steg 1: Noen sikkerhetstiltak, men fortsatt usikker implementasjon:
 * - Token vises på skjermen (ingen e-post)
 * - Token har ikke utløpstid
 * - Ingen rate limiting
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once '../utility/db.php';
require_once '../utility/Template.php';

$pdo = getDB();

$feilmelding = '';
$suksessmelding = '';
$tilbakestillings_token = '';
$tilbakestillings_lenke = '';

// Håndter forespørsel
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epost = trim($_POST['epost'] ?? '');
    
    if (empty($epost)) {
        $feilmelding = 'Vennligst fyll inn e-postadressen din.';
    } elseif (!filter_var($epost, FILTER_VALIDATE_EMAIL)) {
        $feilmelding = 'Ugyldig e-postadresse.';
    } else {
        // Sjekk om brukeren finnes
        $stmt = $pdo->prepare("SELECT user_id, navn, rolle FROM bruker WHERE epost = ?");
        $stmt->execute([$epost]);
        $bruker = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($bruker) {
            // Generer token med bruker-ID og timestamp
            $timestamp = time();
            $data = $bruker['user_id'] . '|' . $timestamp;
            $tilbakestillings_token = base64_encode($data);
            
            $suksessmelding = "Tilbakestillingslenke er generert for {$bruker['navn']}.";
            
            // Generer lenke
            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
            $tilbakestillings_lenke = $base_url . dirname($_SERVER['PHP_SELF']) . "/tilbakestill_passord.php?token=" . urlencode($tilbakestillings_token);
        } else {
            // Sikkerhetsfeil: Vi forteller at brukeren ikke finnes
            // I en sikker implementasjon ville vi ikke avsløre dette
            $feilmelding = 'Ingen bruker med denne e-postadressen ble funnet.';
        }
    }
}

$navigasjon = [
    ['url' => '../index.php', 'tekst' => 'Hjem', 'klasse' => ''],
    ['url' => 'login.php', 'tekst' => 'Logg inn', 'klasse' => '']
];

echo Template::header('Glemt passord | ' . SIDE_TITTEL, 'gjest', $navigasjon);
?>

<div class="register-container" style="max-width: 500px;">
    <h2>Glemt passord?</h2>
    <p class="subtittel">Skriv inn e-postadressen din, så genererer vi et nytt passord for deg.</p>

    <?php 
    if ($feilmelding) {
        echo Template::visFeil($feilmelding);
    }
    
    if ($suksessmelding && $tilbakestillings_lenke) {
        echo Template::visSuksess($suksessmelding);
        echo '<div class="suksess-boks" style="background: #d4edda; border: 1px solid #c3e6cb; padding: 1.5rem; border-radius: 8px; margin: 1rem 0;">';
        echo '<p style="margin: 0 0 0.5rem 0; font-weight: 600;">Din tilbakestillingslenke:</p>';
        echo '<div style="background: white; padding: 0.75rem; border-radius: 4px; margin: 0.5rem 0; word-break: break-all;">';
        echo '<a href="' . htmlspecialchars($tilbakestillings_lenke) . '" style="color: #0066cc; text-decoration: underline;">' . htmlspecialchars($tilbakestillings_lenke) . '</a>';
        echo '</div>';
        echo '<p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #155724;"><strong>Viktig:</strong> Klikk på lenken for å velge et nytt passord. Lenken kan bare brukes én gang.</p>';
        echo '<p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #856404;"><strong>OBS:</strong> I en produksjonsløsning ville denne lenken blitt sendt til din e-post i stedet for å vises her.</p>';
        echo '</div>';
        echo '<div style="text-align: center; margin-top: 1.5rem;">';
        echo '<a href="' . htmlspecialchars($tilbakestillings_lenke) . '" class="btn btn-primary">Gå til tilbakestilling</a>';
        echo '</div>';
    }
    ?>

    <?php if (!$suksessmelding): ?>
    <form method="POST" action="">
        <div class="form-gruppe">
            <label for="epost">E-postadresse</label>
            <input type="email" 
                   id="epost" 
                   name="epost" 
                   placeholder="din.epost@example.no"
                   value="<?php echo htmlspecialchars($_POST['epost'] ?? ''); ?>"
                   required>
            <p class="hjelp">Skriv inn e-postadressen du registrerte deg med</p>
        </div>

        <button type="submit" class="btn btn-primary">
            Tilbakestill passord
        </button>
    </form>

    <div class="ekstra-lenker">
        <p>Husket du passordet? <a href="login.php">Logg inn her</a></p>
        <p><a href="../index.php">← Tilbake til forsiden</a></p>
    </div>
    <?php endif; ?>
</div>

<?php echo Template::footer(); ?>
