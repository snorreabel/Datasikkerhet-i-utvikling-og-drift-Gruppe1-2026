<?php
/**
 * BaseController - Fellesfunksjonalitet for alle sider
 * 
 * Håndterer repeterende oppgaver som:
 * - Auth-sjekk og innlogget bruker
 * - Database-tilkobling
 * - Rolle-validering
 * - Feil/suksess-meldinger
 * - Navigasjon basert på rolle
 * 
 * Steg 1: Ingen sikkerhetstiltak implementert (bevisst).
 */

class BaseController
{
    public $pdo;
    public $bruker;
    public $rolle;
    protected $suksessmelding = '';
    protected $feilmelding = '';

    /**
     * Initialiser controller
     * @param array|null $allowedRoles Liste over tillatte roller. Null = alle innloggede.
     */
    public function __construct($allowedRoles = null)
    {
        session_start();
        date_default_timezone_set('Europe/Oslo');

        // Sjekk innlogging
        if (!AuthService::isLoggedIn()) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->bruker = AuthService::getUser();
        $this->rolle = $this->bruker['rolle'] ?? null;

        // Valider rolle hvis spesifisert
        if ($allowedRoles !== null && !in_array($this->rolle, $allowedRoles)) {
            header('Location: ../shared/dashboard.php');
            exit;
        }

        // Koble til database
        $this->pdo = getDB();
    }

    /**
     * Hent navigasjon basert på rolle
     */
    public function getNavigation()
    {
        switch ($this->rolle) {
            case 'student':
                return [
                    'Dashboard' => '../shared/dashboard.php',
                    'Mine meldinger' => '../shared/student_meldinger.php',
                    'Emner' => '../shared/emner.php',
                    'Profil' => '../shared/profil.php',
                    'Logg ut' => '../auth/logout.php'
                ];

            case 'foreleser':
                return [
                    'Dashboard' => '../shared/dashboard.php',
                    'Meldinger' => '../shared/meldinger.php',
                    'Emner' => '../shared/emner.php',
                    'Rapporter' => '../shared/rapporter.php',
                    'Profil' => '../shared/profil.php',
                    'Logg ut' => '../auth/logout.php'
                ];

            case 'admin':
                return [
                    ['url' => '../shared/dashboard.php', 'tekst' => 'Dashboard', 'klasse' => ''],
                    ['url' => '../auth/logout.php', 'tekst' => 'Logg ut', 'klasse' => 'btn-loggut']
                ];

            default:
                return [];
        }
    }

    /**
     * Hent sidemeny for admin
     */
    public function getAdminSidemeny($activePage = '')
    {
        $menyItems = [
            'dashboard' => ['url' => '../shared/dashboard.php', 'tekst' => 'Dashboard'],
            'brukere' => ['url' => '../admin/brukere.php', 'tekst' => 'Brukere'],
            'meldinger' => ['url' => '../shared/meldinger.php', 'tekst' => 'Meldinger'],
            'rapporter' => ['url' => '../shared/rapporter.php', 'tekst' => 'Rapporter'],
            'emner' => ['url' => '../shared/emner.php', 'tekst' => 'Emner']
        ];

        $html = '<aside class="sidemeny">';
        $html .= '<h3>Navigasjon</h3>';
        $html .= '<nav><ul>';

        foreach ($menyItems as $key => $item) {
            $activeClass = ($activePage === $key) ? ' class="active"' : '';
            $html .= sprintf(
                '<li><a href="%s"%s>%s</a></li>',
                htmlspecialchars($item['url']),
                $activeClass,
                htmlspecialchars($item['tekst'])
            );
        }

        $html .= '</ul></nav>';
        $html .= '</aside>';

        return $html;
    }

    /**
     * Hent sidemeny for foreleser
     */
    public function getForeleserSidemeny($activePage = '')
    {
        $menyItems = [
            'dashboard' => ['url' => '../shared/dashboard.php', 'tekst' => 'Dashboard'],
            'meldinger' => ['url' => '../shared/meldinger.php', 'tekst' => 'Meldinger'],
            'emner' => ['url' => '../shared/emner.php', 'tekst' => 'Emner'],
            'rapporter' => ['url' => '../shared/rapporter.php', 'tekst' => 'Rapporter'],
            'profil' => ['url' => '../shared/profil.php', 'tekst' => 'Profil']
        ];

        $html = '<aside class="sidemeny">';
        $html .= '<h3>Navigasjon</h3>';
        $html .= '<nav><ul>';

        foreach ($menyItems as $key => $item) {
            $activeClass = ($activePage === $key) ? ' class="active"' : '';
            $html .= sprintf(
                '<li><a href="%s"%s>%s</a></li>',
                htmlspecialchars($item['url']),
                $activeClass,
                htmlspecialchars($item['tekst'])
            );
        }

        $html .= '</ul></nav>';
        $html .= '</aside>';

        return $html;
    }

    /**
     * Hent sidemeny for student
     */
    public function getStudentSidemeny($activePage = '')
    {
        $menyItems = [
            'dashboard' => ['url' => '../shared/dashboard.php', 'tekst' => 'Dashboard'],
            'meldinger' => ['url' => '../shared/student_meldinger.php', 'tekst' => 'Meldinger'],
            'emner' => ['url' => '../shared/emner.php', 'tekst' => 'Emner'],
            'profil' => ['url' => '../shared/profil.php', 'tekst' => 'Profil']
        ];

        $html = '<aside class="sidemeny">';
        $html .= '<h3>Navigasjon</h3>';
        $html .= '<nav><ul>';

        foreach ($menyItems as $key => $item) {
            $activeClass = ($activePage === $key) ? ' class="active"' : '';
            $html .= sprintf(
                '<li><a href="%s"%s>%s</a></li>',
                htmlspecialchars($item['url']),
                $activeClass,
                htmlspecialchars($item['tekst'])
            );
        }

        $html .= '</ul></nav>';
        $html .= '</aside>';

        return $html;
    }

    /**
     * Vis meldinger (feil/suksess) som er satt
     */
    public function visMeldinger()
    {
        $html = '';
        if ($this->suksessmelding) {
            $html .= Template::visSuksess($this->suksessmelding);
        }
        if ($this->feilmelding) {
            $html .= Template::visFeil($this->feilmelding);
        }
        return $html;
    }

    /**
     * Sett suksessmelding
     */
    public function setSuksess($melding)
    {
        $this->suksessmelding = $melding;
    }

    /**
     * Sett feilmelding
     */
    public function setFeil($melding)
    {
        $this->feilmelding = $melding;
    }

    /**
     * Redirect til en side
     */
    public function redirect($url)
    {
        header("Location: $url");
        exit;
    }

    /**
     * Sjekk om bruker har rolle
     */
    public function hasRole($rolle)
    {
        return $this->rolle === $rolle;
    }

    /**
     * Sjekk om bruker har en av rollene
     */
    public function hasAnyRole($roller)
    {
        return in_array($this->rolle, $roller);
    }
}
