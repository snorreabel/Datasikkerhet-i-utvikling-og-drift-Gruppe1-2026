<?php
/**
 * Felles innloggingsside
 * 
 * Håndterer innlogging for alle roller (student, foreleser, admin).
 * Rolle bestemmes av URL-parameter eller referer.
 * 
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/AuthService.php';
require_once __DIR__ . '/../utility/Template.php';

// Bestem rolle basert på URL-parameter eller referer
$rolle = $_GET['rolle'] ?? 'student';
$tilbake_url = $_GET['tilbake'] ?? "../{$rolle}/dashboard.php";

// Valider rolle
$gyldige_roller = ['student', 'foreleser', 'admin'];
if (!in_array($rolle, $gyldige_roller)) {
    $rolle = 'student';
}

// Hvis allerede innlogget, redirect til dashboard
if (AuthService::isLoggedIn() && AuthService::hasRole($rolle)) {
    header("Location: ../shared/dashboard.php");
    exit;
}

$feilmelding = '';
$epost = '';

// Håndter innlogging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $epost = $_POST['epost'] ?? '';
    $passord = $_POST['passord'] ?? '';
    $rolle = $_POST['rolle'] ?? $rolle;
    
    $resultat = AuthService::login($epost, $passord, $rolle);
    
    if ($resultat['success']) {
        header("Location: ../shared/dashboard.php");
        exit;
    } else {
        $feilmelding = $resultat['message'];
    }
}

$navigasjon = [
    ['url' => '../index.php', 'tekst' => 'Hjem', 'klasse' => '']
];

echo Template::header("Logg inn - " . ucfirst($rolle) . " | " . SIDE_TITTEL, $rolle, $navigasjon);
?>

        <section class="login-boks">
            <h2>Logg inn</h2>
            <p>Velg din rolle og skriv inn innloggingsopplysninger</p>

            <!-- ============================================================
                 ROLLE-VELGER (FANER)
                 ============================================================ -->

            <nav class="rolle-velger" aria-label="Velg brukertype">
                <a href="?rolle=student"
                   class="<?php echo $rolle === 'student' ? 'aktiv-student' : ''; ?>">
                    Student
                </a>
                <a href="?rolle=foreleser"
                   class="<?php echo $rolle === 'foreleser' ? 'aktiv-foreleser' : ''; ?>">
                    Foreleser
                </a>
                <a href="?rolle=admin"
                   class="<?php echo $rolle === 'admin' ? 'aktiv-admin' : ''; ?>">
                    Admin
                </a>
            </nav>

            <?php 
            if ($feilmelding) {
                echo Template::visFeil($feilmelding);
            }
            ?>

            <form method="POST" action="">
                <input type="hidden" name="rolle" value="<?php echo htmlspecialchars($rolle); ?>">
                
                <div class="skjema-gruppe">
                    <label for="epost">E-post</label>
                    <input type="email" id="epost" name="epost" value="<?php echo htmlspecialchars($epost); ?>" required>
                </div>

                <div class="skjema-gruppe">
                    <label for="passord">Passord</label>
                    <input type="password" id="passord" name="passord" required>
                </div>

                <button type="submit" class="btn btn-primary">Logg inn</button>
            </form>

            <div class="ekstra-lenker">
                <p>Har du ikke bruker? <a href="registrer.php?rolle=<?php echo urlencode($rolle); ?>">Registrer deg her</a></p>
                <p><a href="glemt_passord.php">Glemt passord?</a></p>
                <?php if ($rolle !== 'admin'): ?>
                    <p style="margin-top: 0.5rem;"><a href="../index.php">Tilbake til forsiden</a></p>
                <?php endif; ?>
            </div>
        </section>

<?php echo Template::footer(); ?>
