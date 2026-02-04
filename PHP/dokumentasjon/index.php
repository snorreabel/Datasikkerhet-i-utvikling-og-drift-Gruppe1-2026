<?php
/**
 * Dokumentasjonsside
 *
 * Oversikt over systemets funksjonalitet, arkitektur og sikkerhetsvurderinger.
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/Template.php';

$navigasjon = [
    ['url' => '../index.php', 'tekst' => 'Hjem', 'klasse' => '']
];

echo Template::header('Dokumentasjon | ' . SIDE_TITTEL, 'student', $navigasjon);
?>

<div class="dokumentasjon" style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">

    <header style="margin-bottom: 2rem;">
        <h1 style="color: #2c3e50; font-size: 2rem;">Systemdokumentasjon</h1>
        <p style="color: #7f8c8d;">Anonymt Utdanningsforum - Steg 1</p>
    </header>

    <nav class="innhold-nav" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <h2 style="font-size: 1.2rem; margin-bottom: 1rem; color: #2c3e50;">Innholdsfortegnelse</h2>
        <ol style="margin: 0; padding-left: 1.5rem;">
            <li><a href="#oversikt" style="color: #3498db;">Systemoversikt</a></li>
            <li><a href="#roller" style="color: #3498db;">Brukerroller</a></li>
            <li><a href="#funksjonalitet" style="color: #3498db;">Funksjonalitet</a></li>
            <li><a href="#arkitektur" style="color: #3498db;">Teknisk arkitektur</a></li>
            <li><a href="#sikkerhet" style="color: #3498db;">Sikkerhetsvurderinger</a></li>
            <li><a href="#database" style="color: #3498db;">Databasestruktur</a></li>
            <li><a href="#api" style="color: #3498db;">API-endepunkter</a></li>
        </ol>
    </nav>

    <section id="oversikt" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">1. Systemoversikt</h2>
        <p style="line-height: 1.8; color: #34495e;">
            Anonymt Utdanningsforum er en plattform som lar studenter sende anonyme tilbakemeldinger
            til forelesere om emner de er registrert i. Systemet er utviklet som del av emnet
            "Datasikkerhet i Utvikling og Drift" for a demonstrere vanlige sikkerhetssårbarheter
            i webapplikasjoner.
        </p>
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin: 1rem 0; border-radius: 0 4px 4px 0;">
            <strong>Merk:</strong> Dette er Steg 1 av prosjektet, der sikkerhetstiltak bevisst
            IKKE er implementert for å demonstrere sårbarheter.
        </div>
    </section>

    <section id="roller" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">2. Brukerroller</h2>

        <div style="display: grid; gap: 1rem; margin-top: 1rem;">
            <article style="background: #e8f4fd; padding: 1rem; border-radius: 8px; border-left: 4px solid #3498db;">
                <h3 style="color: #2980b9; margin-bottom: 0.5rem;">Student</h3>
                <ul style="margin: 0; color: #34495e;">
                    <li>Registrere seg med studieprogram og kull</li>
                    <li>Sende anonyme meldinger til forelesere</li>
                    <li>Se egne sendte meldinger</li>
                    <li>Endre passord</li>
                </ul>
            </article>

            <article style="background: #f3e5f5; padding: 1rem; border-radius: 8px; border-left: 4px solid #9b59b6;">
                <h3 style="color: #8e44ad; margin-bottom: 0.5rem;">Foreleser</h3>
                <ul style="margin: 0; color: #34495e;">
                    <li>Se anonyme meldinger for sine emner</li>
                    <li>Svare pa meldinger</li>
                    <li>Se statistikk over tilbakemeldinger</li>
                    <li>Endre passord</li>
                </ul>
            </article>

            <article style="background: #ffebee; padding: 1rem; border-radius: 8px; border-left: 4px solid #e74c3c;">
                <h3 style="color: #c0392b; margin-bottom: 0.5rem;">Admin</h3>
                <ul style="margin: 0; color: #34495e;">
                    <li>Administrere alle brukere (se, endre, slette)</li>
                    <li>Administrere alle meldinger (se, endre, slette)</li>
                    <li>De-anonymisere meldinger (ved behov)</li>
                    <li>Se rapporterte meldinger</li>
                    <li>Tilgang til systemstatistikk</li>
                </ul>
            </article>

            <article style="background: #e8f5e9; padding: 1rem; border-radius: 8px; border-left: 4px solid #27ae60;">
                <h3 style="color: #27ae60; margin-bottom: 0.5rem;">Gjest</h3>
                <ul style="margin: 0; color: #34495e;">
                    <li>Se offentlige meldinger</li>
                    <li>Kommentere pa meldinger (uten innlogging)</li>
                </ul>
            </article>
        </div>
    </section>

    <section id="funksjonalitet" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">3. Funksjonalitet</h2>

        <h3 style="color: #34495e; margin-top: 1.5rem;">Autentisering</h3>
        <ul style="color: #34495e; line-height: 1.8;">
            <li>Innlogging med e-post, passord og rollevalg</li>
            <li>Registrering for studenter og forelesere</li>
            <li>Admin-registrering krever godkjenningskode</li>
            <li>Passordendring for innloggede brukere</li>
        </ul>

        <h3 style="color: #34495e; margin-top: 1.5rem;">Meldingssystem</h3>
        <ul style="color: #34495e; line-height: 1.8;">
            <li>Anonyme meldinger fra studenter</li>
            <li>Meldinger knyttet til spesifikke emner</li>
            <li>Foreleser-svar pa meldinger</li>
            <li>Rapportering av upassende innhold</li>
        </ul>

        <h3 style="color: #34495e; margin-top: 1.5rem;">Administrasjon</h3>
        <ul style="color: #34495e; line-height: 1.8;">
            <li>Brukerhåndtering (CRUD)</li>
            <li>Meldingshåndtering (CRUD)</li>
            <li>De-anonymisering ved regelbrudd</li>
            <li>Statistikk og rapporter</li>
        </ul>
    </section>

    <section id="arkitektur" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">4. Teknisk arkitektur</h2>

        <h3 style="color: #34495e; margin-top: 1.5rem;">Teknologistack</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Komponent</th>
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Teknologi</th>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Backend</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">PHP 7.4+</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Database</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">MySQL (via PDO/MySQLi)</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Webserver</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Apache</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Frontend</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">HTML5, CSS3, JavaScript</td>
            </tr>
        </table>

        <h3 style="color: #34495e; margin-top: 1.5rem;">Mappestruktur</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 8px; overflow-x: auto;">
PHP/
├── admin/           # Admin-sider
├── assets/css/      # Stilark
├── auth/            # Autentisering (login, registrer, logout)
├── dokumentasjon/   # Denne dokumentasjonen
├── emne/            # Emnerelaterte sider
├── foreleser/       # Foreleser-sider
├── student/         # Student-sider
├── utility/         # Hjelpeklasser (Auth, Template, db)
└── index.php        # Forside
        </pre>
    </section>

    <section id="sikkerhet" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">5. Sikkerhetsvurderinger (Steg 1)</h2>

        <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 1rem; margin: 1rem 0; border-radius: 0 4px 4px 0;">
            <strong>Advarsel:</strong> Følgende sårbarheter er bevisst til stede i Steg 1 for
            pedagogiske formål.
        </div>

        <h3 style="color: #c0392b; margin-top: 1.5rem;">Kjente sårbarheter</h3>

        <article style="margin: 1rem 0; padding: 1rem; background: #fff; border: 1px solid #dee2e6; border-radius: 8px;">
            <h4 style="color: #e74c3c;">SQL Injection</h4>
            <p style="color: #666;">Enkelte spørringer bruker direkte strengkonkatenering uten
            parameterisering, noe som gjør systemet sårbart for SQL-injeksjon.</p>
            <p style="color: #666;"><strong>Eksempel:</strong> <code>sok_avsender.php</code> linje 41</p>
        </article>

        <article style="margin: 1rem 0; padding: 1rem; background: #fff; border: 1px solid #dee2e6; border-radius: 8px;">
            <h4 style="color: #e74c3c;">Manglende CSRF-beskyttelse</h4>
            <p style="color: #666;">Skjemaer mangler CSRF-tokens, noe som gjør dem sårbare for
            Cross-Site Request Forgery-angrep.</p>
        </article>

        <article style="margin: 1rem 0; padding: 1rem; background: #fff; border: 1px solid #dee2e6; border-radius: 8px;">
            <h4 style="color: #e74c3c;">Svak passordhåndtering ved registrering</h4>
            <p style="color: #666;">Auth::register() lagrer passord uten hashing i enkelte tilfeller.</p>
        </article>

        <article style="margin: 1rem 0; padding: 1rem; background: #fff; border: 1px solid #dee2e6; border-radius: 8px;">
            <h4 style="color: #e74c3c;">Hardkodet admin-kode</h4>
            <p style="color: #666;">Admin-registreringskoden er hardkodet i kildekoden.</p>
        </article>
    </section>

    <section id="database" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">6. Databasestruktur</h2>

        <h3 style="color: #34495e; margin-top: 1.5rem;">Hovedtabeller</h3>

        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Tabell</th>
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Beskrivelse</th>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>bruker</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Brukerkontoer (user_id, navn, epost, passord, rolle)</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>student_info</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Ekstra info for studenter (studieretning, kull)</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>emne</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Emner (emnekode, emnenavn)</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>melding</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Meldinger (melding_id, innhold, tidspunkt, bruker_user_id, emne_emnekode)</td>
            </tr>
        </table>
    </section>

    <section id="api" style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">7. API-endepunkter</h2>

        <p style="color: #7f8c8d; margin-bottom: 1rem;">Systemet bruker ikke et formelt REST API,
        men følgende PHP-sider fungerer som endepunkter:</p>

        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Endepunkt</th>
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Metode</th>
                <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid #dee2e6;">Beskrivelse</th>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>/auth/login.php</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">POST</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Autentisering</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>/auth/registrer.php</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">POST</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Brukerregistrering</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>/student/send_melding.php</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">POST</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Send anonym melding</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>/admin/brukere.php</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">GET/POST</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Brukerhåndtering</td>
            </tr>
            <tr>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>/admin/meldinger.php</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">GET/POST</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">Meldingshåndtering</td>
            </tr>
            <tr style="background: #f8f9fa;">
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;"><code>/admin/sok_avsender.php</code></td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">GET/POST</td>
                <td style="padding: 0.75rem; border-bottom: 1px solid #dee2e6;">De-anonymisering</td>
            </tr>
        </table>
    </section>

    <footer style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #dee2e6; color: #7f8c8d; text-align: center;">
        <p>Anonymt Utdanningsforum - Steg 1</p>
        <p>Datasikkerhet i Utvikling og Drift - Vår 2025</p>
    </footer>

</div>

<?php echo Template::footer(); ?>
