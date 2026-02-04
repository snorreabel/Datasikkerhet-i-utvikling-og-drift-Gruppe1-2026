<?php
/**
 * API Dokumentasjon
 *
 * Beskrivelse av API-endepunkter for mobil-app integrasjon.
 */

session_start();
date_default_timezone_set('Europe/Oslo');

define('SIDE_TITTEL', 'Anonymt utdanningsforum');

require_once __DIR__ . '/../utility/Template.php';

$navigasjon = [
    ['url' => '../index.php', 'tekst' => 'Hjem', 'klasse' => ''],
    ['url' => '../dokumentasjon/', 'tekst' => 'Dokumentasjon', 'klasse' => '']
];

echo Template::header('API Dokumentasjon | ' . SIDE_TITTEL, 'student', $navigasjon);
?>

<div class="api-dokumentasjon" style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">

    <header style="margin-bottom: 2rem;">
        <h1 style="color: #2c3e50; font-size: 2rem;">API Dokumentasjon</h1>
        <p style="color: #7f8c8d;">Enkelt URL-basert API for mobil-app integrasjon</p>
    </header>

    <div style="background: #e8f4fd; border-left: 4px solid #3498db; padding: 1rem; margin-bottom: 2rem; border-radius: 0 4px 4px 0;">
        <strong>Base URL:</strong> <code>http://158.39.188.217/steg1/api/</code><br>
        <small>Hver fil er et eget endepunkt</small>
    </div>

    <!-- AUTENTISERING -->
    <section style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">Autentisering</h2>

        <article style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin: 1rem 0;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="background: #27ae60; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: bold; font-size: 0.9rem;">POST</span>
                <code style="font-size: 1.1rem;">/login.php</code>
            </div>
            <p style="color: #666; margin-bottom: 1rem;">Logger inn en bruker og oppretter sesjon.</p>

            <h4 style="color: #34495e;">Request Body (form-data):</h4>
            <table style="width: 100%; border-collapse: collapse; margin: 0.5rem 0 1rem;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Parameter</th>
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Type</th>
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Beskrivelse</th>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>epost</code></td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">string</td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Brukerens e-postadresse</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>passord</code></td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">string</td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Brukerens passord</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>rolle</code></td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">string</td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">student / foreleser / admin</td>
                </tr>
            </table>

            <h4 style="color: #34495e;">Response (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "success": true,
  "message": "Innlogging vellykket",
  "user": {
    "user_id": 1,
    "navn": "Ola Nordmann",
    "epost": "ola@student.no",
    "rolle": "student"
  }
}</pre>
        </article>
    </section>

    <!-- EMNER -->
    <section style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">Emner</h2>

        <article style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin: 1rem 0;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="background: #3498db; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: bold; font-size: 0.9rem;">GET</span>
                <code style="font-size: 1.1rem;">/emner.php</code>
            </div>
            <p style="color: #666; margin-bottom: 1rem;">Henter alle tilgjengelige emner. Ingen autentisering kreves.</p>

            <h4 style="color: #34495e;">Response (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "emner": [
    {
      "emnekode": "DAT100",
      "emnenavn": "Grunnleggende programmering",
      "foreleser": "Per Hansen"
    }
  ]
}</pre>
        </article>
    </section>

    <!-- MELDINGER -->
    <section style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">Meldinger</h2>

        <article style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin: 1rem 0;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="background: #3498db; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: bold; font-size: 0.9rem;">GET</span>
                <code style="font-size: 1.1rem;">/meldinger.php?emne=XX&pin=XXXX</code>
            </div>
            <p style="color: #666; margin-bottom: 1rem;">Henter alle meldinger for et emne. Krever gyldig PIN-kode.</p>

            <h4 style="color: #34495e;">Query Parameters:</h4>
            <table style="width: 100%; border-collapse: collapse; margin: 0.5rem 0 1rem;">
                <tr style="background: #f8f9fa;">
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Parameter</th>
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Type</th>
                    <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Beskrivelse</th>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>emne</code></td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">string</td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Emnekode (f.eks. DAT100)</td>
                </tr>
                <tr>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>pin</code></td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">string</td>
                    <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">4-sifret PIN-kode for emnet</td>
                </tr>
            </table>

            <h4 style="color: #34495e;">Response (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "emne": {
    "emnekode": "DAT100",
    "emnenavn": "Grunnleggende programmering",
    "foreleser": "Per Hansen"
  },
  "meldinger": [
    {
      "melding_id": 1,
      "innhold": "Kan vi få mer øvingsoppgaver?",
      "tidspunkt": "2025-01-20 14:30:00"
    }
  ]
}</pre>
        </article>

        <article style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin: 1rem 0;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="background: #27ae60; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: bold; font-size: 0.9rem;">POST</span>
                <code style="font-size: 1.1rem;">/meldinger.php</code>
            </div>
            <p style="color: #666; margin-bottom: 1rem;">Sender en anonym melding til et emne. Krever innlogging som student.</p>

            <h4 style="color: #34495e;">Request Body (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "emnekode": "DAT100",
  "innhold": "Kan vi få mer øvingsoppgaver?"
}</pre>

            <h4 style="color: #34495e;">Response (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "success": true,
  "melding_id": 42
}</pre>
        </article>
    </section>

    <!-- SVAR -->
    <section style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">Svar (Foreleser)</h2>

        <article style="background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 1.5rem; margin: 1rem 0;">
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                <span style="background: #27ae60; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-weight: bold; font-size: 0.9rem;">POST</span>
                <code style="font-size: 1.1rem;">/svar.php</code>
            </div>
            <p style="color: #666; margin-bottom: 1rem;">Foreleser svarer på en melding. Krever innlogging som foreleser.</p>

            <h4 style="color: #34495e;">Request Body (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "melding_id": 1,
  "innhold": "Ja, jeg legger ut flere øvingsoppgaver!"
}</pre>

            <h4 style="color: #34495e;">Response (JSON):</h4>
            <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "success": true,
  "kommentar_id": 5
}</pre>
        </article>
    </section>

    <!-- FEILHÅNDTERING -->
    <section style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">Feilhåndtering</h2>

        <p style="line-height: 1.8; color: #34495e;">Alle API-endepunkter returnerer JSON med feilmelding ved feil:</p>

        <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">{
  "error": "Beskrivelse av feilen"
}</pre>

        <h3 style="color: #34495e; margin-top: 1.5rem;">HTTP Statuskoder</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 1rem 0;">
            <tr style="background: #f8f9fa;">
                <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Kode</th>
                <th style="padding: 0.5rem; text-align: left; border-bottom: 1px solid #dee2e6;">Beskrivelse</th>
            </tr>
            <tr>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>200</code></td>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">OK - Forespørsel vellykket</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>400</code></td>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Bad Request - Mangler påkrevde felt</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>401</code></td>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Unauthorized - Ikke innlogget / ugyldig PIN</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>403</code></td>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Forbidden - Ingen tilgang</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>404</code></td>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Not Found - Ressurs ikke funnet</td>
            </tr>
            <tr>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;"><code>405</code></td>
                <td style="padding: 0.5rem; border-bottom: 1px solid #dee2e6;">Method Not Allowed - Feil HTTP-metode</td>
            </tr>
        </table>
    </section>

    <!-- EKSEMPLER -->
    <section style="margin-bottom: 3rem;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 0.5rem;">Eksempel: cURL</h2>

        <h3 style="color: #34495e;">Innlogging</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">curl -X POST http://158.39.188.217/steg1/api/login.php \
  -d "epost=ola@student.no" \
  -d "passord=hemmelig123" \
  -d "rolle=student" \
  -c cookies.txt</pre>

        <h3 style="color: #34495e; margin-top: 1rem;">Hent meldinger for emne</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">curl "http://158.39.188.217/steg1/api/meldinger.php?emne=DAT100&pin=1234"</pre>

        <h3 style="color: #34495e; margin-top: 1rem;">Send melding (krever sesjon)</h3>
        <pre style="background: #2c3e50; color: #ecf0f1; padding: 1rem; border-radius: 4px; overflow-x: auto;">curl -X POST http://158.39.188.217/steg1/api/meldinger.php \
  -H "Content-Type: application/json" \
  -d '{"emnekode":"DAT100","innhold":"Kan vi få mer øvingsoppgaver?"}' \
  -b cookies.txt</pre>
    </section>

    <footer style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #dee2e6; color: #7f8c8d; text-align: center;">
        <p>Anonymt Utdanningsforum API - Steg 1</p>
        <p>Datasikkerhet i Utvikling og Drift - Vår 2025</p>
    </footer>

</div>

<?php echo Template::footer(); ?>
