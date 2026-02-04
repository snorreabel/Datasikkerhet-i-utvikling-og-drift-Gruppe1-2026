<?php
/**
 * Template Helper - Header & Footer
 * 
 * Sentralisert header og footer for konsistent utseende på alle sider.
 */

class Template {
    
    /**
     * Generer HTML-header med navigasjon
     * 
     * @param string $tittel Sidetittel
     * @param string $rolle Brukerrolle (student, foreleser, admin, gjest)
     * @param array $navigasjon Array med navigasjonslenker ['tekst' => 'url']
     */
    public static function header($tittel = 'Anonymt utdanningsforum', $rolle = null, $navigasjon = []) {
        $rolle_klasse = $rolle ? "rolle-{$rolle}" : '';
        ?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($tittel) ?> | Anonymt utdanningsforum</title>
    <link rel="stylesheet" href="<?= self::getCSSPath() ?>">
</head>
<body class="<?= $rolle_klasse ?>">
    <nav class="hovednavigasjon">
        <h1><a href="<?= self::getBasePath() ?>/index.php">Anonymt utdanningsforum</a></h1>
        
        <?php if (!empty($navigasjon)): ?>
        <ul>
            <?php foreach ($navigasjon as $key => $item): ?>
                <?php if (is_array($item)): ?>
                    <!-- Nytt format: array med 'url', 'tekst', 'klasse' -->
                    <li><a href="<?= htmlspecialchars($item['url']) ?>" class="nav-knapp <?= htmlspecialchars($item['klasse'] ?? '') ?>"><?= htmlspecialchars($item['tekst']) ?></a></li>
                <?php else: ?>
                    <!-- Gammelt format: 'tekst' => 'url' -->
                    <li><a href="<?= htmlspecialchars($item) ?>" class="nav-knapp"><?= htmlspecialchars($key) ?></a></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </nav>
    
    <main class="hovedinnhold">
        <?php
    }
    
    /**
     * Generer HTML-footer
     */
    public static function footer() {
        ?>
    </main>
    
    <footer class="sidefot">
        <div style="display: flex; justify-content: center; align-items: center; width: 100%; position: relative;">
            <div style="text-align: center;">
                <p>&copy; <?= date('Y') ?> Anonymt utdanningsforum - Fase 1</p>
                <p><small>Utviklet for undervisningsformål - Datasikkerhet i Utvikling og Drift</small></p>
            </div>
            <div style="position: absolute; right: 0; display: flex; gap: 1rem; align-items: center;">
                <a href="<?= self::getBasePath() ?>/auth/registrer_admin.php" style="font-size: 0.85rem; color: #34495e; text-decoration: underline; cursor: pointer;">Admin Registrering</a>
                <a href="<?= self::getBasePath() ?>/auth/login.php?rolle=admin" style="font-size: 0.85rem; color: #34495e; text-decoration: underline; cursor: pointer;">Admin Login</a>
            </div>
        </div>
    </footer>
</body>
</html>
        <?php
    }
    
    /**
     * Vis suksessmelding
     */
    public static function visSuksess($melding) {
        if (!empty($melding)) {
            echo '<div class="melding melding-suksess">' . htmlspecialchars($melding) . '</div>';
        }
    }
    
    /**
     * Vis feilmelding
     */
    public static function visFeil($melding) {
        if (!empty($melding)) {
            echo '<div class="melding melding-feil">' . htmlspecialchars($melding) . '</div>';
        }
    }
    
    /**
     * Vis info-melding
     */
    public static function visInfo($melding) {
        if (!empty($melding)) {
            echo '<div class="melding melding-info">' . htmlspecialchars($melding) . '</div>';
        }
    }
    
    /**
     * Generer sidemeny basert på rolle
     * 
     * @param string $rolle Brukerrolle (student, foreleser, admin)
     * @param string $aktivSide Nøkkel for aktiv side
     * @return string HTML for sidemeny
     */
    public static function genererSidemeny($rolle, $aktivSide = '') {
        $menyItems = [];
        
        switch ($rolle) {
            case 'student':
                $menyItems = [
                    'dashboard' => ['url' => self::getBasePath() . '/shared/dashboard.php', 'tekst' => 'Dashboard', 'ikon' => '🏠'],
                    'meldinger' => ['url' => self::getBasePath() . '/shared/student_meldinger.php', 'tekst' => 'Meldinger', 'ikon' => '💬'],
                    'emner' => ['url' => self::getBasePath() . '/shared/emner.php', 'tekst' => 'Emner', 'ikon' => '📚'],
                    'profil' => ['url' => self::getBasePath() . '/shared/profil.php', 'tekst' => 'Profil', 'ikon' => '👤']
                ];
                break;
                
            case 'foreleser':
                $menyItems = [
                    'dashboard' => ['url' => self::getBasePath() . '/shared/dashboard.php', 'tekst' => 'Dashboard', 'ikon' => '🏠'],
                    'meldinger' => ['url' => self::getBasePath() . '/shared/meldinger.php', 'tekst' => 'Meldinger', 'ikon' => '💬'],
                    'emner' => ['url' => self::getBasePath() . '/shared/emner.php', 'tekst' => 'Emner', 'ikon' => '📚'],
                    'rapporter' => ['url' => self::getBasePath() . '/shared/rapporter.php', 'tekst' => 'Rapporter', 'ikon' => '🚩'],
                    'profil' => ['url' => self::getBasePath() . '/shared/profil.php', 'tekst' => 'Profil', 'ikon' => '👤']
                ];
                break;
                
            case 'admin':
                $menyItems = [
                    'dashboard' => ['url' => self::getBasePath() . '/shared/dashboard.php', 'tekst' => 'Dashboard', 'ikon' => ''],
                    'brukere' => ['url' => self::getBasePath() . '/admin/brukere.php', 'tekst' => 'Brukere', 'ikon' => ''],
                    'meldinger' => ['url' => self::getBasePath() . '/shared/meldinger.php', 'tekst' => 'Meldinger', 'ikon' => ''],
                    'rapporter' => ['url' => self::getBasePath() . '/shared/rapporter.php', 'tekst' => 'Rapporter', 'ikon' => ''],
                    'emner' => ['url' => self::getBasePath() . '/shared/emner.php', 'tekst' => 'Emner', 'ikon' => '']
                ];
                break;
                
            default:
                return '';
        }
        
        $html = '<aside class="sidemeny">';
        $html .= '<h3>Navigasjon</h3>';
        $html .= '<nav><ul>';
        
        foreach ($menyItems as $key => $item) {
            $activeClass = ($aktivSide === $key) ? ' class="active"' : '';
            $html .= sprintf(
                '<li><a href="%s"%s><span class="ikon">%s</span> %s</a></li>',
                htmlspecialchars($item['url']),
                $activeClass,
                $item['ikon'],
                htmlspecialchars($item['tekst'])
            );
        }
        
        $html .= '</ul></nav>';
        $html .= '</aside>';
        
        return $html;
    }
    
    /**
     * Generer navigasjon for innlogget bruker
     */
    public static function genererNav($rolle) {
        $nav = [];
        
        switch ($rolle) {
            case 'student':
                $nav = [
                    'Dashboard' => self::getBasePath() . '/shared/dashboard.php',
                    'Meldinger' => self::getBasePath() . '/shared/student_meldinger.php',
                    'Emner' => self::getBasePath() . '/shared/emner.php',
                    'Profil' => self::getBasePath() . '/shared/profil.php',
                    'Logg ut' => self::getBasePath() . '/auth/logout.php'
                ];
                break;
                
            case 'foreleser':
                $nav = [
                    'Dashboard' => self::getBasePath() . '/shared/dashboard.php',
                    'Meldinger' => self::getBasePath() . '/shared/meldinger.php',
                    'Emner' => self::getBasePath() . '/shared/emner.php',
                    'Rapporter' => self::getBasePath() . '/shared/rapporter.php',
                    'Profil' => self::getBasePath() . '/shared/profil.php',
                    'Logg ut' => self::getBasePath() . '/auth/logout.php'
                ];
                break;
                
            case 'admin':
                $nav = [
                    'Dashboard' => self::getBasePath() . '/shared/dashboard.php',
                    'Brukere' => self::getBasePath() . '/admin/brukere.php',
                    'Meldinger' => self::getBasePath() . '/shared/meldinger.php',
                    'Rapporter' => self::getBasePath() . '/shared/rapporter.php',
                    'Emner' => self::getBasePath() . '/shared/emner.php',
                    'Logg ut' => self::getBasePath() . '/auth/logout.php'
                ];
                break;
                
            default:
                $nav = [
                    'Hjem' => self::getBasePath() . '/index.php'
                ];
        }
        
        return $nav;
    }
    
    /**
     * Hent base path for applikasjonen
     */
    private static function getBasePath() {
        return '/steg1';
    }
    
    /**
     * Hent CSS-sti - absolutt path fra webroot
     */
    private static function getCSSPath() {
        return '/steg1/assets/css/stil.css';
    }
}
