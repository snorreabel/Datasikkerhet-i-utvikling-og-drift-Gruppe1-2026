<?php
/**
 * Registreringsside - Felles for alle roller
 *
 * Håndterer registrering for:
 * - Student: Grunnleggende brukerinfo
 * - Foreleser: Brukerinfo + bilde (emne er valgfritt, kan opprettes fra dashboard)
 * - Admin: Deaktivert (kan aktiveres senere)
 *
 * Rolle bestemmes av URL-parameter: ?rolle=student|foreleser|admin
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once '../utility/db.php';
$pdo = getDB();

// ============================================================================
// KONFIGURASJON
// ============================================================================

$gyldige_roller = ['student', 'foreleser', 'admin'];
$rolle = isset($_GET['rolle']) ? $_GET['rolle'] : 'student';

if (!in_array($rolle, $gyldige_roller)) {
    $rolle = 'student';
}

// Admin-registreringskode (påkrevd for admin-registrering)
define('ADMIN_REGISTRERING_KODE', '19391945');

// Fargekonfigurasjon per rolle
$farger = [
    'student' => [
        'hoved' => '#2c3e50',
        'aksent' => '#3498db',
        'bakgrunn' => '#f5f5f5',
        'tekst' => '#2c3e50',
        'gradient' => 'linear-gradient(135deg, #3498db 0%, #2c3e50 100%)'
    ],
    'foreleser' => [
        'hoved' => '#8e44ad',
        'aksent' => '#9b59b6',
        'bakgrunn' => '#f5f3f7',
        'tekst' => '#2c3e50',
        'gradient' => 'linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%)'
    ],
    'admin' => [
        'hoved' => '#c0392b',
        'aksent' => '#e74c3c',
        'bakgrunn' => '#1a1a2e',
        'tekst' => '#eaeaea',
        'gradient' => 'linear-gradient(135deg, #e74c3c 0%, #c0392b 100%)'
    ]
];

$fargepalett = $farger[$rolle];

// ============================================================================
// VARIABLER
// ============================================================================

$feilmelding = '';
$suksessmelding = '';

// Behold skjemadata ved feil
$form_data = [
    'navn' => '',
    'epost' => '',
    // Student-spesifikke
    'studieretning' => '',
    'kull' => '',
    // Foreleser-spesifikke
    'emnekode' => '',
    'emnenavn' => '',
    'pin_kode' => ''
];

// ============================================================================
// HÅNDTER INNSENDING
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Hent rolle fra skjema (hidden field)
    $rolle = $_POST['rolle'] ?? $rolle;
    if (!in_array($rolle, $gyldige_roller)) {
        $rolle = 'student';
    }

    // Felles felter for alle roller
    $form_data['navn'] = trim($_POST['navn'] ?? '');
    $form_data['epost'] = trim($_POST['epost'] ?? '');
    $passord = $_POST['passord'] ?? '';
    $bekreft_passord = $_POST['bekreft_passord'] ?? '';

    // Admin-spesifikke felter
    $admin_kode = '';
    if ($rolle === 'admin') {
        $admin_kode = trim($_POST['admin_kode'] ?? '');
    }

    // ========================================================================
    // VALIDERING - FELLES
    // ========================================================================

    if (empty($form_data['navn']) || empty($form_data['epost']) || empty($passord)) {
        $feilmelding = "Alle obligatoriske felter må fylles ut.";
    } elseif ($passord !== $bekreft_passord) {
        $feilmelding = "Passordene matcher ikke.";
    } elseif (!filter_var($form_data['epost'], FILTER_VALIDATE_EMAIL)) {
        $feilmelding = "Ugyldig e-postadresse.";
    } elseif (strlen($passord) < 6) {
        $feilmelding = "Passordet må være minst 6 tegn.";
    }

    // ========================================================================
    // VALIDERING - ADMIN (godkjenningskode)
    // ========================================================================

    if ($rolle === 'admin' && empty($feilmelding)) {
        if ($admin_kode !== ADMIN_REGISTRERING_KODE) {
            $feilmelding = "Ugyldig godkjenningskode for admin-registrering.";
        }
    }

    // ========================================================================
    // VALIDERING - STUDENT (ekstra felter)
    // ========================================================================

    if ($rolle === 'student' && empty($feilmelding)) {
        $form_data['studieretning'] = trim($_POST['studieretning'] ?? '');
        $form_data['kull'] = trim($_POST['kull'] ?? '');

        if (empty($form_data['studieretning']) || empty($form_data['kull'])) {
            $feilmelding = "Studieretning og kull er påkrevd for studenter.";
        }
    }

    // ========================================================================
    // VALIDERING - FORELESER (ekstra felter)
    // ========================================================================

    if ($rolle === 'foreleser' && empty($feilmelding)) {
        $form_data['emnekode'] = trim($_POST['emnekode'] ?? '');
        $form_data['emnenavn'] = trim($_POST['emnenavn'] ?? '');
        $form_data['pin_kode'] = trim($_POST['pin_kode'] ?? '');

        // Valider emne-felter kun hvis noen av dem er fylt ut
        $har_emnedata = !empty($form_data['emnekode']) || !empty($form_data['emnenavn']) || !empty($form_data['pin_kode']);

        if ($har_emnedata) {
            // Hvis noe er fylt ut, må alt fylles ut
            if (empty($form_data['emnekode']) || empty($form_data['emnenavn']) || empty($form_data['pin_kode'])) {
                $feilmelding = "Hvis du vil opprette et emne, må emnekode, emnenavn og PIN-kode fylles ut.";
            } elseif (!preg_match('/^\d{4}$/', $form_data['pin_kode'])) {
                $feilmelding = "PIN-kode må være nøyaktig 4 siffer.";
            }

            // Sjekk om emnekode allerede eksisterer
            if (empty($feilmelding)) {
                $stmt = $pdo->prepare("SELECT emnekode FROM emne WHERE emnekode = ?");
                $stmt->execute([$form_data['emnekode']]);
                if ($stmt->fetch()) {
                    $feilmelding = "Emnekoden er allerede i bruk.";
                }
            }
        }

        // Valider bilde
        if (empty($feilmelding)) {
            if (!isset($_FILES['bilde']) || $_FILES['bilde']['error'] === UPLOAD_ERR_NO_FILE) {
                $feilmelding = "Profilbilde er påkrevd for forelesere.";
            } elseif ($_FILES['bilde']['error'] !== UPLOAD_ERR_OK) {
                $feilmelding = "Feil ved opplasting av bilde. Feilkode: " . $_FILES['bilde']['error'];
            } else {
                $tillatte_typer = ['image/jpeg', 'image/png', 'image/gif'];
                $filtype = $_FILES['bilde']['type'];
                if (!in_array($filtype, $tillatte_typer)) {
                    $feilmelding = "Kun JPG, PNG og GIF er tillatt.";
                } elseif ($_FILES['bilde']['size'] > 5 * 1024 * 1024) {
                    $feilmelding = "Bildet kan ikke være større enn 5MB.";
                }
            }
        }
    }

    // ========================================================================
    // DATABASE - SJEKK OM E-POST FINNES
    // ========================================================================

    if (empty($feilmelding)) {
        $stmt = $pdo->prepare("SELECT user_id FROM bruker WHERE epost = ?");
        $stmt->execute([$form_data['epost']]);
        if ($stmt->fetch()) {
            $feilmelding = "E-postadressen er allerede registrert.";
        }
    }

    // ========================================================================
    // DATABASE - OPPRETT BRUKER
    // ========================================================================

    if (empty($feilmelding)) {
        $hashet_passord = password_hash($passord, PASSWORD_DEFAULT);

        // Start transaksjon for foreleser (bruker + emne + bilde må lykkes sammen)
        if ($rolle === 'foreleser') {
            $pdo->beginTransaction();
        }

        try {
            // ==============================================================
            // OPPRETT BRUKER (alle roller bruker samme grunnleggende SQL)
            // ==============================================================

            $stmt = $pdo->prepare("INSERT INTO bruker (navn, epost, passord, rolle) VALUES (?, ?, ?, ?)");
            $stmt->execute([$form_data['navn'], $form_data['epost'], $hashet_passord, $rolle]);

            $bruker_id = $pdo->lastInsertId();

            // ==============================================================
            // STUDENT: Opprett student_info med studieretning og kull
            // ==============================================================

            if ($rolle === 'student') {
                $stmt = $pdo->prepare("INSERT INTO student_info (bruker_user_id, studieretning, kull) VALUES (?, ?, ?)");
                $stmt->execute([$bruker_id, $form_data['studieretning'], $form_data['kull']]);
            }

            // ==============================================================
            // FORELESER: Opprett emne (valgfritt) og last opp bilde
            // ==============================================================

            if ($rolle === 'foreleser') {
                // Opprett emne kun hvis emnedata er fylt ut
                if (!empty($form_data['emnekode']) && !empty($form_data['emnenavn']) && !empty($form_data['pin_kode'])) {
                    $stmt = $pdo->prepare("INSERT INTO emne (emnekode, emnenavn, pin_kode, bruker_user_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$form_data['emnekode'], $form_data['emnenavn'], $form_data['pin_kode'], $bruker_id]);
                }

                // Last opp bilde til assets/bilder/
                $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'bilder' . DIRECTORY_SEPARATOR;

                // Opprett mappe hvis den ikke eksisterer
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        throw new Exception("Kunne ikke opprette bildemappe: " . $upload_dir);
                    }
                }

                // Sjekk at mappen er skrivbar
                if (!is_writable($upload_dir)) {
                    throw new Exception("Bildemappen er ikke skrivbar: " . $upload_dir);
                }

                $filendelse = strtolower(pathinfo($_FILES['bilde']['name'], PATHINFO_EXTENSION));
                $nytt_filnavn = 'foreleser_' . $bruker_id . '_' . time() . '.' . $filendelse;
                $maalsti = $upload_dir . $nytt_filnavn;

                if (!move_uploaded_file($_FILES['bilde']['tmp_name'], $maalsti)) {
                    throw new Exception("Kunne ikke lagre bilde til: " . $maalsti);
                }

                // Commit transaksjon
                $pdo->commit();
            }

            // Suksess!
            $suksessmelding = "Registrering vellykket! Du blir sendt til innlogging...";
            header("refresh:2;url=login.php?rolle=$rolle");

        } catch (Exception $e) {
            if ($rolle === 'foreleser') {
                $pdo->rollBack();
            }
            $feilmelding = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../utility/Template.php';

$navigasjon = [
    ['url' => '../index.php', 'tekst' => 'Hjem', 'klasse' => ''],
    ['url' => 'login.php?rolle=' . $rolle, 'tekst' => 'Logg inn', 'klasse' => '']
];

echo Template::header("Registrer deg - " . ucfirst($rolle) . " | " . SIDE_TITTEL, $rolle, $navigasjon);
?>

    <!-- ====================================================================
         HOVEDINNHOLD
         ==================================================================== -->

        <div class="register-container">
            <h2>Registrer deg</h2>
            <p class="subtittel">Opprett en konto for å komme i gang</p>

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

            <!-- ============================================================
                 MELDINGER
                 ============================================================ -->

            <?php 
            if ($feilmelding) {
                echo Template::visFeil($feilmelding);
            }
            if ($suksessmelding) {
                echo Template::visSuksess($suksessmelding);
            }
            ?>

            <!-- ============================================================
                 REGISTRERINGSSKJEMA
                 ============================================================ -->

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="rolle" value="<?php echo htmlspecialchars($rolle); ?>">

                <!-- ========================================================
                     SEKSJON 1: PERSONLIG INFORMASJON (ALLE ROLLER)
                     ======================================================== -->

                <div class="form-seksjon">
                    <h3>Personlig informasjon</h3>

                    <div class="form-gruppe">
                        <label for="navn">Fullt navn *</label>
                        <input type="text"
                               id="navn"
                               name="navn"
                               value="<?php echo htmlspecialchars($form_data['navn']); ?>"
                               placeholder="Ola Nordmann"
                               required>
                    </div>

                    <div class="form-gruppe">
                        <label for="epost">E-post *</label>
                        <input type="email"
                               id="epost"
                               name="epost"
                               value="<?php echo htmlspecialchars($form_data['epost']); ?>"
                               placeholder="din.epost@example.no"
                               required>
                    </div>

                    <div class="form-gruppe">
                        <label for="passord">Passord *</label>
                        <input type="password"
                               id="passord"
                               name="passord"
                               placeholder="Minst 6 tegn"
                               required>
                        <p class="hjelp">Minimum 6 tegn</p>
                    </div>

                    <div class="form-gruppe">
                        <label for="bekreft_passord">Bekreft passord *</label>
                        <input type="password"
                               id="bekreft_passord"
                               name="bekreft_passord"
                               placeholder="Gjenta passordet"
                               required>
                    </div>
                </div>

                <!-- ========================================================
                     SEKSJON 2A: ADMIN - GODKJENNINGSKODE
                     ======================================================== -->

                <?php if ($rolle === 'admin'): ?>
                <div class="form-seksjon" style="background-color: #fff3cd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <h3>Godkjenningskode</h3>
                    <p style="color: #856404; margin-bottom: 1rem;">Admin-registrering krever en godkjenningskode</p>

                    <div class="form-gruppe">
                        <label for="admin_kode">Godkjenningskode *</label>
                        <input type="password"
                               id="admin_kode"
                               name="admin_kode"
                               placeholder="Skriv inn godkjenningskoden"
                               required>
                        <p class="hjelp">Denne koden er påkrevd for å opprette admin-brukere</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================
                     SEKSJON 2B: STUDENT - STUDIEINFORMASJON
                     ======================================================== -->

                <?php if ($rolle === 'student'): ?>
                <div class="form-seksjon">
                    <h3>Studieinformasjon</h3>

                    <div class="form-gruppe">
                        <label for="studieretning">Studieretning *</label>
                        <input type="text"
                               id="studieretning"
                               name="studieretning"
                               value="<?php echo htmlspecialchars($form_data['studieretning']); ?>"
                               placeholder="F.eks. Dataingeniør, IT-drift"
                               maxlength="100"
                               required>
                    </div>

                    <div class="form-gruppe">
                        <label for="kull">Kull/årskull *</label>
                        <input type="text"
                               id="kull"
                               name="kull"
                               value="<?php echo htmlspecialchars($form_data['kull']); ?>"
                               placeholder="F.eks. 2024 eller 2024H"
                               maxlength="20"
                               required>
                        <p class="hjelp">Året du startet eller semester (f.eks. 2024H for høst)</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================
                     SEKSJON 3: FORELESER - PROFILBILDE
                     ======================================================== -->

                <?php if ($rolle === 'foreleser'): ?>
                <div class="form-seksjon">
                    <h3>Profilbilde</h3>

                    <div class="form-gruppe">
                        <label for="bilde">Last opp bilde *</label>
                        <input type="file"
                               id="bilde"
                               name="bilde"
                               accept="image/jpeg,image/png,image/gif"
                               required>
                        <p class="hjelp">JPG, PNG eller GIF. Maks 5MB.</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================
                     SEKSJON 4: FORELESER - FØRSTE EMNE (VALGFRITT)
                     ======================================================== -->

                <?php if ($rolle === 'foreleser'): ?>
                <div class="form-seksjon">
                    <h3>Opprett et emne (valgfritt)</h3>
                    <p class="hjelp" style="margin-bottom: 1rem;">
                        Du kan hoppe over dette og opprette emner senere fra dashboardet.
                    </p>

                    <div class="form-gruppe">
                        <label for="emnekode">Emnekode</label>
                        <input type="text"
                               id="emnekode"
                               name="emnekode"
                               value="<?php echo htmlspecialchars($form_data['emnekode']); ?>"
                               placeholder="F.eks. DAT101"
                               maxlength="45">
                    </div>

                    <div class="form-gruppe">
                        <label for="emnenavn">Emnenavn</label>
                        <input type="text"
                               id="emnenavn"
                               name="emnenavn"
                               value="<?php echo htmlspecialchars($form_data['emnenavn']); ?>"
                               placeholder="F.eks. Grunnleggende programmering"
                               maxlength="100">
                    </div>

                    <div class="form-gruppe">
                        <label for="pin_kode">PIN-kode (4 siffer)</label>
                        <input type="text"
                               id="pin_kode"
                               name="pin_kode"
                               value="<?php echo htmlspecialchars($form_data['pin_kode']); ?>"
                               placeholder="0000"
                               pattern="[0-9]{4}"
                               maxlength="4"
                               inputmode="numeric">
                        <p class="hjelp">Gjester bruker denne for å se emnet</p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ========================================================
                     SEND-KNAPP
                     ======================================================== -->

                <button type="submit" class="btn btn-primary">
                    Registrer deg som <?php echo $rolle; ?>
                </button>

            </form>

            <!-- ============================================================
                 EKSTRA LENKER
                 ============================================================ -->

            <div class="ekstra-lenker">
                <p>Har du allerede en konto?
                   <a href="login.php?rolle=<?php echo $rolle; ?>">Logg inn her</a>
                </p>
                <p><a href="../index.php">← Tilbake til forsiden</a></p>
            </div>

        </div>

<?php echo Template::footer(); ?>
