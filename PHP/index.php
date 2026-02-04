<?php
/**
 * Anonym Tilbakemelding - Hovedside
 * 
 * Landingsside der gjester skriver PIN-kode for a se meldinger,
 * og studenter/forelesere kan registrere seg eller logge inn.
 */

require_once 'utility/Template.php';

date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');
define('PIN_LENGDE', 4);

$navigasjon = [
    ['url' => 'auth/registrer.php?rolle=student', 'tekst' => 'Registrer deg', 'klasse' => 'btn-registrer'],
    ['url' => 'auth/login.php?rolle=student', 'tekst' => 'Logg inn', 'klasse' => 'btn-logginn']
];

echo Template::header('Anonymt utdanningsforum - Fase 1', 'gjest', $navigasjon);
?>

        <section class="hero" aria-labelledby="hero-tittel">
            <h2 id="hero-tittel">Send anonyme meldinger og kommentarer!</h2>
        </section>

        <section class="pin-seksjon" aria-labelledby="pin-tittel">
            <h3 id="pin-tittel">Gå til et emne</h3>
            <p>Skriv inn emnets firesifrede PIN-kode for å få tilgang</p>
            <form action="shared/vis_emne.php" method="GET" role="search">
                <label for="pin" class="visually-hidden">PIN-kode</label>
                <input 
                    type="text" 
                    id="pin"
                    name="pin" 
                    class="pin-input" 
                    placeholder="0000" 
                    maxlength="<?php echo PIN_LENGDE; ?>" 
                    pattern="[0-9]{<?php echo PIN_LENGDE; ?>}"
                    inputmode="numeric"
                    autocomplete="off"
                    aria-describedby="pin-hjelp"
                    required
                >
                <small id="pin-hjelp" class="visually-hidden">Skriv inn 4 siffer</small>
                <button type="submit" class="btn btn-send">Vis emne</button>
            </form>
        </section>

        <section class="info-seksjon" aria-label="Informasjon for brukere">
            <article>
                <h3>Student?</h3>
                <p>Registrer deg for å sende anonyme meldinger til forelesere i dine emner.</p>
                <a href="auth/registrer.php?rolle=student">Registrer deg</a>
            </article>
            <article>
                <h3>Foreleser?</h3>
                <p>Registrer deg for å motta tilbakemeldinger og svare studentene.</p>
                <a href="auth/registrer.php?rolle=foreleser">Registrer deg</a>
            </article>
        </section>

<?php echo Template::footer(); ?>
