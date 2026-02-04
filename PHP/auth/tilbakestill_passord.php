<?php
/**
 * Tilbakestill passord
 * 
 * La brukeren velge et nytt passord ved hjelp av en tilbakestillingstoken.
 * 
 * Steg 1: Grunnleggende sikkerhet, men mangler:
 * - Token utløpstid
 * - Rate limiting
 * - CSRF beskyttelse
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once '../utility/db.php';
require_once '../utility/Template.php';

$pdo = getDB();

$feilmelding = '';
$suksessmelding = '';
$token = $_GET['token'] ?? '';
$gyldig_token = false;
$bruker = null;
$bruker_id = null;

// Valider token (usignert token uten database)
if (!empty($token)) {
    try {
        $decoded = base64_decode($token);
        $parts = explode('|', $decoded);
        
        if (count($parts) === 2) {
            list($user_id, $timestamp) = $parts;
            
            // Hent brukerinfo
            $stmt = $pdo->prepare("SELECT user_id, navn, epost FROM bruker WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $bruker = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($bruker) {
                $gyldig_token = true;
                $bruker_id = $bruker['user_id'];
                $bruker['bruker_user_id'] = $bruker['user_id']; // For kompatibilitet
            } else {
                $feilmelding = 'Bruker ikke funnet.';
            }
        } else {
            $feilmelding = 'Ugyldig token-format.';
        }
    } catch (Exception $e) {
        $feilmelding = 'Kunne ikke validere token.';
    }
} else {
    $feilmelding = 'Ingen tilbakestillingstoken oppgitt.';
}

// Håndter passordbytte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $gyldig_token) {
    $nytt_passord = $_POST['passord'] ?? '';
    $bekreft_passord = $_POST['bekreft_passord'] ?? '';
    $post_token = $_POST['token'] ?? '';
    
    // Valider at token matcher
    if ($post_token !== $token) {
        $feilmelding = 'Ugyldig forespørsel.';
    } elseif (empty($nytt_passord)) {
        $feilmelding = 'Vennligst skriv inn et nytt passord.';
    } elseif (strlen($nytt_passord) < 6) {
        $feilmelding = 'Passordet må være minst 6 tegn langt.';
    } elseif ($nytt_passord !== $bekreft_passord) {
        $feilmelding = 'Passordene stemmer ikke overens.';
    } else {
        // Oppdater passord
        $hashet_passord = password_hash($nytt_passord, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE bruker SET passord = ? WHERE user_id = ?");
        if ($stmt->execute([$hashet_passord, $bruker['bruker_user_id']])) {
            $suksessmelding = 'Passordet ditt er oppdatert! Du kan nå logge inn med ditt nye passord.';
            $gyldig_token = false; // Hindre at skjema vises igjen
        } else {
            $feilmelding = 'Kunne ikke oppdatere passordet. Prøv igjen senere.';
        }
    }
}

$navigasjon = [
    ['url' => '../index.php', 'tekst' => 'Hjem', 'klasse' => ''],
    ['url' => 'login.php', 'tekst' => 'Logg inn', 'klasse' => '']
];

echo Template::header('Tilbakestill passord | ' . SIDE_TITTEL, 'gjest', $navigasjon);
?>

<div class="register-container" style="max-width: 500px;">
    <h2>Tilbakestill passord</h2>
    
    <?php if ($bruker && $gyldig_token): ?>
        <p class="subtittel">Hei <?php echo htmlspecialchars($bruker['navn']); ?>! Velg et nytt passord.</p>
    <?php else: ?>
        <p class="subtittel">Oppgi et nytt passord for din konto.</p>
    <?php endif; ?>

    <?php 
    if ($feilmelding) {
        echo Template::visFeil($feilmelding);
    }
    
    if ($suksessmelding) {
        echo Template::visSuksess($suksessmelding);
        echo '<div style="text-align: center; margin-top: 1.5rem;">';
        echo '<a href="login.php" class="btn btn-primary">Gå til innlogging</a>';
        echo '</div>';
    }
    ?>

    <?php if ($gyldig_token && !$suksessmelding): ?>
    <form method="POST" action="">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        
        <div class="form-gruppe">
            <label for="passord">Nytt passord</label>
            <input type="password" 
                   id="passord" 
                   name="passord" 
                   placeholder="Minimum 6 tegn"
                   minlength="6"
                   required>
            <p class="hjelp">Velg et sikkert passord med minst 6 tegn</p>
        </div>

        <div class="form-gruppe">
            <label for="bekreft_passord">Bekreft nytt passord</label>
            <input type="password" 
                   id="bekreft_passord" 
                   name="bekreft_passord" 
                   placeholder="Gjenta passordet"
                   minlength="6"
                   required>
        </div>

        <button type="submit" class="btn btn-primary">
            Lagre nytt passord
        </button>
    </form>
    <?php elseif (!$suksessmelding): ?>
        <div class="ekstra-lenker">
            <p><a href="glemt_passord.php">Få ny tilbakestillingslenke</a></p>
            <p><a href="../index.php">← Tilbake til forsiden</a></p>
        </div>
    <?php endif; ?>
    
    <?php if (!$gyldig_token && !$suksessmelding): ?>
    <div class="ekstra-lenker">
        <p><a href="glemt_passord.php">Få ny tilbakestillingslenke</a></p>
        <p><a href="../index.php">← Tilbake til forsiden</a></p>
    </div>
    <?php endif; ?>
</div>

<?php echo Template::footer(); ?>
